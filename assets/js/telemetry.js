/**
 * Python4Physics - Client-Side Telemetry & Engagement Tracker
 * Tracks active engagement time, scroll depth, page views, and simulation execution events.
 */
(function() {
  'use strict';

  // Base configuration
  const siteUrl = window.p4p_siteurl || (window.location.origin + '/');
  const endpoint = siteUrl + 'api/telemetry.php';

  // Session Management
  let sessionId = null;
  try {
    sessionId = sessionStorage.getItem('p4p_sid');
    if (!sessionId) {
      sessionId = 'p4p_s_' + Math.random().toString(36).substring(2, 10) + Date.now().toString(36);
      sessionStorage.setItem('p4p_sid', sessionId);
    }
  } catch (e) {
    sessionId = 'p4p_s_' + Date.now().toString(36);
  }

  let visitId = null;
  let activeSeconds = 0;
  let maxScrollPct = 0;
  let isPageVisible = !document.hidden;
  let lastActiveTimestamp = Date.now();
  let heartbeatTimer = null;

  // Helper to send beacon or fetch
  function sendTelemetry(data, isUnload) {
    try {
      const payloadStr = JSON.stringify(data);
      if (isUnload && navigator.sendBeacon) {
        const blob = new Blob([payloadStr], { type: 'application/json' });
        navigator.sendBeacon(endpoint, blob);
        return;
      }
      fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: payloadStr,
        keepalive: isUnload ? true : false,
        credentials: 'omit'
      }).then(res => res.json()).then(res => {
        if (res && res.visit_id) {
          visitId = res.visit_id;
        }
      }).catch(function() {
        // Silently tolerate network drops
      });
    } catch (err) {
      // Ignore
    }
  }

  // Calculate current scroll depth
  function updateScrollDepth() {
    try {
      const docHeight = Math.max(
        document.body.scrollHeight, document.documentElement.scrollHeight,
        document.body.offsetHeight, document.documentElement.offsetHeight,
        document.body.clientHeight, document.documentElement.clientHeight
      );
      const winHeight = window.innerHeight || document.documentElement.clientHeight;
      const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
      if (docHeight > winHeight) {
        const currentPct = Math.min(100, Math.round(((scrollTop + winHeight) / docHeight) * 100));
        if (currentPct > maxScrollPct) {
          maxScrollPct = currentPct;
        }
      } else {
        maxScrollPct = 100;
      }
    } catch (e) {}
  }

  // Active time tick
  function tickActiveTime() {
    if (isPageVisible) {
      const now = Date.now();
      const elapsed = Math.round((now - lastActiveTimestamp) / 1000);
      if (elapsed > 0 && elapsed < 30) {
        activeSeconds += elapsed;
      }
      lastActiveTimestamp = now;
    }
  }

  // Periodic heartbeat
  function sendHeartbeat(isUnload) {
    tickActiveTime();
    updateScrollDepth();
    if (!visitId && !isUnload) return;

    sendTelemetry({
      action: 'heartbeat',
      visit_id: visitId,
      session_id: sessionId,
      duration_seconds: activeSeconds,
      scroll_depth_pct: maxScrollPct
    }, isUnload);
  }

  // Event category derivation
  function deriveCategory(name) {
    name = (name || '').toLowerCase();
    if (name.includes('circuit')) return 'circuit';
    if (name.includes('arduino')) return 'arduino';
    if (name.includes('python') || name.includes('simulation')) return 'simulation';
    if (name.includes('gnuplot')) return 'gnuplot';
    if (name.includes('latex')) return 'latex';
    if (name.includes('export') || name.includes('download')) return 'export';
    if (name.includes('search')) return 'search';
    return 'engagement';
  }

  // Hook or wrap global trackPhysicsEvent
  const originalTracker = window.trackPhysicsEvent;
  window.trackPhysicsEvent = function(eventName, params) {
    params = params || {};

    // Forward to GA4 / GTM if present
    try {
      if (typeof gtag === 'function') {
        gtag('event', eventName, params);
      }
      if (window.dataLayer && Array.isArray(window.dataLayer)) {
        window.dataLayer.push(Object.assign({ event: eventName }, params));
      }
    } catch (e) {}

    // Also run any pre-existing tracker
    if (typeof originalTracker === 'function' && originalTracker !== window.trackPhysicsEvent) {
      try { originalTracker(eventName, params); } catch (e) {}
    }

    // Ingest into first-party analytics
    try {
      sendTelemetry({
        action: 'event',
        visit_id: visitId,
        session_id: sessionId,
        event_name: eventName,
        event_category: params.category || deriveCategory(eventName),
        event_label: params.preset || params.algo || params.title || params.format || params.label || '',
        execution_time_ms: params.execution_time_ms || params.latency || null,
        status: params.status || 'success',
        meta: params
      }, false);
    } catch (e) {}
  };

  // Initialize Page View
  function init() {
    updateScrollDepth();

    // 1. Initial Page View
    sendTelemetry({
      action: 'page_view',
      session_id: sessionId,
      url: window.location.href,
      title: document.title,
      referrer: document.referrer || '',
      screen_res: (window.screen ? (window.screen.width + 'x' + window.screen.height) : '')
    }, false);

    // 2. Tab Visibility Listener
    document.addEventListener('visibilitychange', function() {
      if (document.hidden) {
        isPageVisible = false;
        sendHeartbeat(false);
      } else {
        isPageVisible = true;
        lastActiveTimestamp = Date.now();
      }
    });

    // 3. Scroll depth tracker
    window.addEventListener('scroll', updateScrollDepth, { passive: true });

    // 4. Heartbeat interval (every 15 seconds)
    heartbeatTimer = setInterval(function() {
      if (isPageVisible) {
        sendHeartbeat(false);
      }
    }, 15000);

    // 5. Unload / Page Hide listeners
    window.addEventListener('pagehide', function() {
      sendHeartbeat(true);
    });
    window.addEventListener('beforeunload', function() {
      sendHeartbeat(true);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
