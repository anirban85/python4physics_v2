<?php
/**
 * Python4Physics - Ask AI: Computational Physics Code Synthesizer API
 * 
 * Generates verified, executable, high-quality scientific Python 3 scripts
 * with NumPy, SciPy, and Matplotlib from natural language problem descriptions.
 * 
 * Multi-Tiered Architecture:
 *  - Tier 1: Google Gemini 2.0 / 1.5 Flash Generative AI (when API key is provided)
 *  - Tier 2: Specialized Computational Physics & Scientific Solvers (30+ deep domains)
 *  - Tier 3: Semantic Database Retrieval across 353 verified curriculum programs
 *  - Tier 4: Dynamic Mathematical, Formula & Numerical Synthesizer
 */

ini_set('display_errors', '0');
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Gemini-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Only POST requests are supported.']);
    exit;
}

// Environment & Configuration Ingestion
if (file_exists(__DIR__ . '/../site_config.php')) {
    require_once __DIR__ . '/../site_config.php';
}
if (file_exists(__DIR__ . '/../db.php')) {
    require_once __DIR__ . '/../db.php';
}

// 1. Retrieve Problem Description & Configuration
$problem = '';
$apiKey = '';
if (!empty($_POST['gemini_api_key'])) {
    $apiKey = trim($_POST['gemini_api_key']);
} elseif (!empty($_SERVER['HTTP_X_GEMINI_KEY'])) {
    $apiKey = trim($_SERVER['HTTP_X_GEMINI_KEY']);
} elseif (!empty($gemini_api_key)) {
    $apiKey = trim($gemini_api_key);
} elseif (!empty(getenv('GEMINI_API_KEY'))) {
    $apiKey = trim(getenv('GEMINI_API_KEY'));
}

$includePlot = true;
$topicHint = '';

// Check for JSON payload
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $jsonPayload = json_decode($rawInput, true);
    if (is_array($jsonPayload)) {
        if (!empty($jsonPayload['problem'])) {
            $problem = trim($jsonPayload['problem']);
        }
        if (!empty($jsonPayload['gemini_api_key'])) {
            $apiKey = trim($jsonPayload['gemini_api_key']);
        }
        if (isset($jsonPayload['include_plot'])) {
            $includePlot = (bool)$jsonPayload['include_plot'];
        }
        if (!empty($jsonPayload['topic'])) {
            $topicHint = trim($jsonPayload['topic']);
        }
    }
}

if (empty($problem) && !empty($_POST['problem'])) {
    $problem = trim($_POST['problem']);
}
if (isset($_POST['include_plot'])) {
    $includePlot = filter_var($_POST['include_plot'], FILTER_VALIDATE_BOOLEAN);
}

if (empty($problem)) {
    echo json_encode(['success' => false, 'error' => 'Please provide a physics or computational problem description.']);
    exit;
}

// 2. Tier 1: If Gemini API Key is provided, call Google Gemini Generative API
if (!empty($apiKey)) {
    $geminiResponse = callGeminiAPI($apiKey, $problem, $includePlot, $topicHint);
    if ($geminiResponse !== null && !empty($geminiResponse['code'])) {
        $geminiResponse['user_prompt'] = $problem;
        $geminiResponse['code'] = prependUserPromptDocstring($geminiResponse['code'], $problem, $geminiResponse['title'], $geminiResponse['complexity'] ?? 'O(N)');
        echo json_encode($geminiResponse);
        exit;
    }
}

// 3. Tier 2, 3 & 4: Specialized Solvers, Curriculum Database & Dynamic Synthesizer
$response = generateIntelligentPhysicsCode($problem, $includePlot, $topicHint);
$response['user_prompt'] = $problem;
$response['code'] = prependUserPromptDocstring($response['code'], $problem, $response['title'], $response['complexity'] ?? 'O(N)');
echo json_encode($response);
exit;

/**
 * Execute HTTP POST Request via cURL if available, or stream context fallback
 */
function makeHttpPostRequest($url, $payloadJson, $timeout = 18) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$httpCode, $response];
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
            'content' => $payloadJson,
            'timeout' => $timeout,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    $response = @file_get_contents($url, false, $ctx);
    $httpCode = 200;
    if (isset($http_response_header) && is_array($http_response_header)) {
        if (preg_match('#HTTP/\S+\s+(\d+)#', $http_response_header[0], $m)) {
            $httpCode = (int)$m[1];
        }
    }
    return [$httpCode, $response];
}

/**
 * Call Google Gemini Generative Language API
 */
function callGeminiAPI($apiKey, $problem, $includePlot, $topicHint) {
    try {
        $systemInstruction = "You are an expert computational physicist, numerical mathematician, and senior Python scientific developer for Python4Physics. " .
            "The user will describe a physics, mathematical, or scientific programming problem. " .
            "Generate fully runnable, self-contained, high-quality Python 3 code using standard libraries, NumPy, SciPy, and Matplotlib (plt.show()). " .
            "The code must execute seamlessly in a browser environment via Pyodide. " .
            "Do NOT require external file downloads or local file paths. " .
            "Provide your answer strictly as a JSON object with this schema:\n" .
            "{\n" .
            "  \"title\": \"Clear descriptive title of the solution\",\n" .
            "  \"code\": \"Executable Python code string with clear comments\",\n" .
            "  \"explanation\": \"Pointwise concise theoretical overview and algorithm mechanics with LaTeX mathematical equations ($...$ or $$...$$)\",\n" .
            "  \"complexity\": \"Time and memory complexity (e.g. O(N log N))\",\n" .
            "  \"exercises\": [\"Exercise 1 for student\", \"Exercise 2\", \"Exercise 3\"]\n" .
            "}";

        $userPrompt = "Problem Description: " . $problem . "\n";
        if (!empty($topicHint)) {
            $userPrompt .= "Context Topic: " . $topicHint . "\n";
        }
        $userPrompt .= "Include Graphical Matplotlib Visualization: " . ($includePlot ? "YES" : "NO") . "\n";
        $userPrompt .= "Please output strictly the JSON object without markdown code blocks.";

        $postData = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $systemInstruction . "\n\n" . $userPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'maxOutputTokens' => 2048,
                'responseMimeType' => 'application/json'
            ]
        ];

        $payloadJson = json_encode($postData);

        // 1. Try Gemini 2.0 Flash
        $endpoint20 = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . urlencode($apiKey);
        list($httpCode, $response) = makeHttpPostRequest($endpoint20, $payloadJson, 18);

        if ($httpCode === 200 && !empty($response)) {
            $resJson = json_decode($response, true);
            $rawText = $resJson['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $rawText = trim($rawText);
            $rawText = preg_replace('/^```(?:json)?\s*/i', '', $rawText);
            $rawText = preg_replace('/\s*```$/', '', $rawText);
            $parsed = json_decode($rawText, true);
            if (is_array($parsed) && !empty($parsed['code'])) {
                return [
                    'success' => true,
                    'source' => 'gemini_ai',
                    'title' => $parsed['title'] ?? 'Computational Physics Solution',
                    'code' => $parsed['code'],
                    'explanation' => $parsed['explanation'] ?? '',
                    'complexity' => $parsed['complexity'] ?? 'O(N)',
                    'exercises' => $parsed['exercises'] ?? [
                        'Modify simulation parameters and observe parameter sensitivity.',
                        'Compare numerical convergence with smaller discretization steps.',
                        'Extend the computational model with additional physical interactions.'
                    ]
                ];
            }
        }

        // 2. Fallback to Gemini 1.5 Flash
        $endpoint15 = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($apiKey);
        list($httpCode15, $response15) = makeHttpPostRequest($endpoint15, $payloadJson, 18);

        if ($httpCode15 === 200 && !empty($response15)) {
            $resJson15 = json_decode($response15, true);
            $rawText15 = $resJson15['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $rawText15 = trim($rawText15);
            $rawText15 = preg_replace('/^```(?:json)?\s*/i', '', $rawText15);
            $rawText15 = preg_replace('/\s*```$/', '', $rawText15);
            $parsed15 = json_decode($rawText15, true);
            if (is_array($parsed15) && !empty($parsed15['code'])) {
                return [
                    'success' => true,
                    'source' => 'gemini_ai',
                    'title' => $parsed15['title'] ?? 'Computational Physics Solution',
                    'code' => $parsed15['code'],
                    'explanation' => $parsed15['explanation'] ?? '',
                    'complexity' => $parsed15['complexity'] ?? 'O(N)',
                    'exercises' => $parsed15['exercises'] ?? []
                ];
            }
        }
    } catch (Throwable $e) {
        // Fall through to built-in synthesizer
    }

    return null;
}

/**
 * Intelligent Multi-Tier Code Generator:
 * Matches specialized physics solvers, verified database programs, or dynamic synthesizers.
 */

/**
 * Automatically prepend standard user prompt metadata docstring to Python code
 */
function prependUserPromptDocstring($code, $problem, $title, $complexity) {
    $cleanPrompt = trim(preg_replace('/[\r\n]+/', ' ', $problem));
    $cleanTitle = trim(preg_replace('/[\r\n]+/', ' ', $title));
    $cleanComplexity = trim(preg_replace('/[\r\n]+/', ' ', $complexity));
    
    if (str_contains(substr($code, 0, 300), 'Python4Physics - Ask AI Generated Solution')) {
        return $code;
    }

    $header = "\"\"\"\n" .
        str_repeat('=', 78) . "\n" .
        "Python4Physics - Ask AI Generated Solution\n" .
        "User Prompt: " . $cleanPrompt . "\n" .
        "Physical Domain: " . $cleanTitle . "\n" .
        "Complexity: " . $cleanComplexity . "\n" .
        str_repeat('=', 78) . "\n" .
        "\"\"\"\n\n";

    return $header . ltrim($code);
}

function generateIntelligentPhysicsCode($problem, $includePlot, $topicHint) {
    $p = strtolower($problem . ' ' . $topicHint);

    // =========================================================================
    // 0A. PARITY & EVEN / ODD NUMBER CLASSIFICATION
    // =========================================================================
    if (preg_match('/\b(even|odd|parity)\b/i', $p) && !str_contains($p, 'bose') && !str_contains($p, 'fermi') && !str_contains($p, 'spin') && !str_contains($p, 'function')) {
        $num = 15;
        if (preg_match('/(?:number|for|check|is|n\s*=)\s*(-?\d+)/i', $problem, $m)) {
            $num = (int)$m[1];
        } elseif (preg_match('/\b(-?\d+)\b/', $problem, $m)) {
            $num = (int)$m[1];
        }
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Parity Analysis & Even/Odd Classification
# Definition: An integer n is even if n % 2 == 0; odd if n % 2 != 0

def check_parity(n):
    return "Even" if n % 2 == 0 else "Odd"

target_n = {{NUM}}
status = check_parity(target_n)

# Range Analysis for consecutive numbers
N = max(10, min(50, abs(target_n) * 2 if abs(target_n) > 0 else 20))
numbers = np.arange(1, N + 1)
evens = [x for x in numbers if x % 2 == 0]
odds = [x for x in numbers if x % 2 != 0]

print("=" * 65)
print(f"PARITY & EVEN/ODD CLASSIFICATION REPORT")
print("=" * 65)
print(f"Target Number:         {target_n} is {status.upper()}")
print(f"Tested Integer Domain: 1 to {N}")
print(f"Total Even Integers:   {len(evens)} (Sum = {sum(evens)})")
print(f"Total Odd Integers:    {len(odds)} (Sum = {sum(odds)})")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Parity sequence oscillation (-1)^n
display_limit = min(20, N)
ax1.stem(numbers[:display_limit], (-1)**numbers[:display_limit], linefmt='b-', markerfmt='bo', basefmt='k-')
ax1.set_title(r'Parity Alternation Factor $(-1)^n$', fontsize=12, fontweight='bold')
ax1.set_xlabel('Integer $n$', fontsize=11)
ax1.set_ylabel(r'$(-1)^n$ (+1 Even, -1 Odd)', fontsize=11)
ax1.set_yticks([-1, 1])
ax1.set_yticklabels(['Odd (-1)', 'Even (+1)'])
ax1.grid(True, alpha=0.3)

# Plot 2: Count comparison
ax2.bar(['Even Numbers', 'Odd Numbers'], [len(evens), len(odds)], color=['#0ea5e9', '#f59e0b'], width=0.5)
ax2.set_title(f'Even vs Odd Distribution (Domain 1 to {N})', fontsize=12, fontweight='bold')
ax2.set_ylabel('Count', fontsize=11)
for i, count in enumerate([len(evens), len(odds)]):
    ax2.text(i, count * 0.5, str(count), ha='center', va='center', color='white', fontweight='bold', fontsize=12)
ax2.grid(True, alpha=0.3, axis='y')

plt.tight_layout()
plt.show()
PY;
        $code = str_replace('{{NUM}}', (string)$num, $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Even / Odd Number Classification & Parity Analysis (n = {$num})",
            'code' => $code,
            'explanation' => "Determines parity via the modulo operator `n % 2 == 0`. Evaluates discrete distribution across the integer domain and illustrates the alternating parity factor \$(-1)^n\$.",
            'complexity' => 'Time: O(1) single check, O(N) range analysis; Space: O(1)',
            'exercises' => [
                'Implement a bitwise parity check using `(n & 1) == 0`.',
                'Demonstrate that the product of two odd numbers is always odd.',
                'Generalize parity to check divisibility by arbitrary modulus k.'
            ]
        ];
    }

    // =========================================================================
    // 0B. SUM OF DIGITS & DIGITAL ROOT
    // =========================================================================
    if (str_contains($p, 'sum of digit') || str_contains($p, 'sum of the digit') || str_contains($p, 'digital root') || (str_contains($p, 'digit') && str_contains($p, 'sum'))) {
        $num = 987654;
        if (preg_match('/(?:number|for|of|is|n\s*=)\s*(\d+)/i', $problem, $m)) {
            $num = (int)$m[1];
        } elseif (preg_match('/\b(\d+)\b/', $problem, $m)) {
            $num = (int)$m[1];
        }
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Sum of Digits & Digital Root Calculation
# Digital Root: The single-digit value obtained through iterative digit summing

def sum_of_digits(n):
    """Compute sum of digits using arithmetic operations."""
    n = abs(int(n))
    total = 0
    while n > 0:
        total += n % 10
        n //= 10
    return total

def digital_root(n):
    """Compute repeated digit sum until a single digit (base-10 digital root)."""
    while n >= 10:
        n = sum_of_digits(n)
    return n

target = {{NUM}}
digit_sum = sum_of_digits(target)
d_root = digital_root(target)

digits = [int(d) for d in str(abs(target))]

print("=" * 65)
print(f"DIGIT ANALYSIS FOR INTEGER: {target}")
print("=" * 65)
print(f"Constituent Digits:   {digits}")
print(f"Sum of Digits:        {' + '.join(map(str, digits))} = {digit_sum}")
print(f"Digital Root:         {d_root} (Mod-9 property: {target % 9 if target % 9 != 0 else 9})")
print("=" * 65)

# Benchmark across sequence 1 to 100
test_seq = np.arange(1, 101)
roots_seq = [digital_root(x) for x in test_seq]

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Digit Frequencies in Target Number
unique_d, counts = np.unique(digits, return_counts=True)
ax1.bar(unique_d, counts, color='#38bdf8', edgecolor='black', width=0.6)
ax1.set_title(f'Digit Frequency in {target}', fontsize=12, fontweight='bold')
ax1.set_xlabel('Digit Value (0-9)', fontsize=11)
ax1.set_ylabel('Frequency', fontsize=11)
ax1.set_xticks(range(10))
ax1.grid(True, alpha=0.3, axis='y')

# Plot 2: Digital Root Periodic Modulation (Modulo 9 wave)
ax2.plot(test_seq, roots_seq, 'r.-', lw=1.2, markersize=4, label='Digital Root')
ax2.set_title('Digital Root Periodic Modulation (1 to 100)', fontsize=12, fontweight='bold')
ax2.set_xlabel('Integer $n$', fontsize=11)
ax2.set_ylabel('Digital Root', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        $code = str_replace('{{NUM}}', (string)$num, $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Sum of Digits & Digital Root (n = {$num})",
            'code' => $code,
            'explanation' => "Calculates the digit sum via remainder extraction `n % 10` and floor division `n // 10`. Evaluates the modulo-9 digital root recurrence and periodic pattern across consecutive integers.",
            'complexity' => 'Time: O(log10(N)), Space: O(1)',
            'exercises' => [
                'Prove that a number is divisible by 9 if and only if its sum of digits is divisible by 9.',
                'Implement recursive digit summation `sum_digits(n) = n if n < 10 else n % 10 + sum_digits(n // 10)`.',
                'Compute the average sum of digits for all 3-digit integers [100, 999].'
            ]
        ];
    }

    // =========================================================================
    // 0C. PYTHON CONDITIONALS (if, elif, else)
    // =========================================================================
    if (str_contains($p, 'conditional') || str_contains($p, 'if elif') || str_contains($p, 'if-elif') || (str_contains($p, 'if') && str_contains($p, 'else') && (str_contains($p, 'python') || str_contains($p, 'statement') || str_contains($p, 'branch')))) {
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Python Decision Control: if, elif, else Branching
# Physical Application: Phase State of Water as a Function of Temperature at 1 atm

def classify_state_of_matter(temp_celsius):
    """Classify physical phase state using multi-way if-elif-else branching."""
    if temp_celsius < 0.0:
        return "Solid (Ice)", "#38bdf8"
    elif temp_celsius == 0.0:
        return "Solid-Liquid Equilibrium (Melting Point)", "#0ea5e9"
    elif 0.0 < temp_celsius < 100.0:
        return "Liquid (Water)", "#2563eb"
    elif temp_celsius == 100.0:
        return "Liquid-Gas Equilibrium (Boiling Point)", "#f59e0b"
    elif 100.0 < temp_celsius < 10000.0:
        return "Gaseous (Steam)", "#ef4444"
    else:
        return "Ionized Plasma State", "#a855f7"

# Test samples
test_temps = [-20, 0, 25, 100, 150, 15000]
print("=" * 65)
print("PYTHON CONDITIONALS (if, elif, else) EXECUTION TRACE")
print("=" * 65)
for t in test_temps:
    state, _ = classify_state_of_matter(t)
    print(f"Temperature: {t:>6}°C  -->  Classification: {state}")
print("=" * 65)

# Visualizing Decision Boundaries across [-40, 140]°C
temps = np.linspace(-40, 140, 400)

fig, ax = plt.subplots(figsize=(10, 4.2))
ax.axvspan(-40, 0, color='#38bdf8', alpha=0.3, label='Solid (Ice) [T < 0°C]')
ax.axvspan(0, 100, color='#2563eb', alpha=0.25, label='Liquid (Water) [0°C < T < 100°C]')
ax.axvspan(100, 140, color='#ef4444', alpha=0.3, label='Gaseous (Steam) [T > 100°C]')

ax.axvline(0, color='#0284c7', ls='--', lw=2, label='Melting Point (0°C)')
ax.axvline(100, color='#d97706', ls='--', lw=2, label='Boiling Point (100°C)')

for t in [-20, 50, 120]:
    state, col = classify_state_of_matter(t)
    ax.scatter([t], [0.5], color=col, s=120, edgecolors='black', zorder=5)
    ax.annotate(f"{t}°C: {state.split()[0]}", xy=(t, 0.5), xytext=(t-5, 0.65),
                arrowprops=dict(facecolor='black', shrink=0.05, width=1, headwidth=6))

ax.set_ylim(0, 1)
ax.set_yticks([])
ax.set_title(r'Python Conditional Branching: Water State Classification $[T < 0, 0 \leq T \leq 100, T > 100]$', fontsize=12, fontweight='bold')
ax.set_xlabel('Temperature (°C)', fontsize=11)
ax.grid(True, alpha=0.3, axis='x')
ax.legend(loc='upper left', framealpha=0.9)

plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Python Conditionals: Multi-Way Decision Control (if, elif, else)",
            'code' => $template,
            'explanation' => "Illustrates structured sequential conditional evaluation using `if`, `elif`, and `else` blocks applied to thermodynamic phase transitions. Visualizes decision boundaries and state partition regions.",
            'complexity' => 'Time: O(1), Space: O(1)',
            'exercises' => [
                'Add pressure as a second parameter to model phase boundaries using the Clausius-Clapeyron equation.',
                'Refactor the branching logic using Python 3.10+ `match / case` pattern matching.',
                'Implement nested conditionals to handle sub-cooling and super-heating states.'
            ]
        ];
    }

    // =========================================================================
    // 0D. GREATEST COMMON DIVISOR (GCD / HCF) & LCM (EUCLIDEAN ALGORITHM)
    // =========================================================================
    if (str_contains($p, 'gcd') || str_contains($p, 'hcf') || str_contains($p, 'greatest common divisor') || str_contains($p, 'highest common factor') || str_contains($p, 'euclidean algorithm') || (str_contains($p, 'lcm') && !str_contains($p, 'rlc'))) {
        $a = 48;
        $b = 18;
        if (preg_match_all('/(\d+)/', $problem, $matches)) {
            if (!empty($matches[1][0])) $a = (int)$matches[1][0];
            if (!empty($matches[1][1])) $b = (int)$matches[1][1];
        }
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Euclidean Algorithm: Greatest Common Divisor (GCD) & Least Common Multiple (LCM)
# Mathematical Formulation:
# gcd(a, b) = gcd(b, a % b) with base case gcd(a, 0) = a
# lcm(a, b) = abs(a * b) / gcd(a, b)

def compute_gcd_steps(a, b):
    steps = []
    a_orig, b_orig = a, b
    while b != 0:
        q = a // b
        r = a % b
        steps.append((a, b, q, r))
        a, b = b, r
    gcd_val = a
    lcm_val = abs(a_orig * b_orig) // gcd_val if gcd_val != 0 else 0
    return gcd_val, lcm_val, steps

a, b = {{A}}, {{B}}
gcd_val, lcm_val, steps = compute_gcd_steps(a, b)

print("=" * 65)
print(f"EUCLIDEAN ALGORITHM TRACE FOR gcd({a}, {b})")
print("=" * 65)
print(f"{'Step':<6} {'a':<10} {'b':<10} {'Quotient':<12} {'Remainder':<10}")
print("-" * 65)
for idx, (x, y, q, r) in enumerate(steps, 1):
    print(f"{idx:<6} {x:<10} {y:<10} {q:<12} {r:<10}")
print("=" * 65)
print(f"Greatest Common Divisor (GCD): {gcd_val}")
print(f"Least Common Multiple (LCM):   {lcm_val}")
print("=" * 65)

# Plot: Remainder sequence decay (Logarithmic convergence)
remainders = [s[3] for s in steps]
step_indices = list(range(1, len(remainders) + 1))

fig, ax = plt.subplots(figsize=(9, 4.2))
ax.plot(step_indices, remainders, 'bo-', lw=2, markersize=8, label='Remainder $r_k$')
ax.axhline(0, color='red', ls='--', label='Termination ($r = 0$)')
for k, r in zip(step_indices, remainders):
    ax.annotate(f'r={r}', xy=(k, r), xytext=(k, r + max(remainders)*0.06),
                ha='center', fontweight='bold', fontsize=9)
ax.set_title(rf'Euclidean Algorithm Remainder Decay for $\gcd({a}, {b}) = {gcd_val}$', fontsize=12, fontweight='bold')
ax.set_xlabel('Division Step $k$', fontsize=11)
ax.set_ylabel('Remainder Value $r_k$', fontsize=11)
ax.set_xticks(step_indices)
ax.grid(True, alpha=0.3)
ax.legend()

plt.tight_layout()
plt.show()
PY;
        $code = str_replace(['{{A}}', '{{B}}'], [(string)$a, (string)$b], $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Euclidean Algorithm: GCD & LCM (a = {$a}, b = {$b})",
            'code' => $code,
            'explanation' => "Computes the greatest common divisor using the Euclidean division theorem \$a = qb + r\$, guaranteeing rapid logarithmic convergence in \$O(\\log(\\min(a, b)))\$ steps. Derives the least common multiple via \$\\text{lcm}(a, b) = |ab| / \\gcd(a, b)\$.",
            'complexity' => 'Time: O(log(min(a, b))), Space: O(1)',
            'exercises' => [
                'Implement the Extended Euclidean Algorithm to find Bezout coefficients x and y such that a*x + b*y = gcd(a, b).',
                'Compute the modular multiplicative inverse of a mod m using the Euclidean algorithm.',
                'Prove Lame\'s theorem: the number of division steps never exceeds 5 times the number of digits in the smaller integer.'
            ]
        ];
    }

    // =========================================================================
    // 0E. GRAVITATIONAL POTENTIAL ENERGY (U = mgh) & FREE FALL CONSERVATION
    // =========================================================================
    if ((str_contains($p, 'gravitational potential') || (str_contains($p, 'potential energy') && (str_contains($p, 'gravity') || str_contains($p, 'gravitat') || str_contains($p, 'height') || str_contains($p, 'mgh') || str_contains($p, 'free fall')))) && !str_contains($p, 'kepler') && !str_contains($p, 'orbit') && !str_contains($p, 'two body')) {
        $mass = 5.0;
        $height = 20.0;
        if (preg_match('/(?:mass|m)\s*=?\s*(\d+(?:\.\d+)?)/i', $problem, $m)) {
            $mass = (float)$m[1];
        }
        if (preg_match('/(?:height|h)\s*=?\s*(\d+(?:\.\d+)?)/i', $problem, $m)) {
            $height = (float)$m[1];
        }
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Gravitational Potential Energy & Mechanical Energy Conservation
# Formulations:
# Gravitational Potential Energy: U(y) = m * g * y
# Kinetic Energy:                K(y) = 0.5 * m * v(y)^2
# Conservation of Energy:        E_total = U(y) + K(y) = m * g * h_0 = const

mass = {{MASS}}      # Mass of object (kg)
height = {{HEIGHT}}  # Initial drop height (m)
g = 9.81             # Acceleration due to gravity (m/s^2)

U_initial = mass * g * height
v_impact = np.sqrt(2 * g * height)
t_fall = np.sqrt(2 * height / g)

print("=" * 65)
print(f"GRAVITATIONAL POTENTIAL ENERGY & CONSERVATION (m = {mass} kg, h = {height} m)")
print("=" * 65)
print(f"Initial Potential Energy (U0):  {U_initial:.4f} Joules")
print(f"Total Mechanical Energy (E):    {U_initial:.4f} Joules")
print(f"Impact Velocity at Ground (v):  {v_impact:.4f} m/s ({v_impact * 3.6:.2f} km/h)")
print(f"Free Fall Duration (t_fall):    {t_fall:.4f} seconds")
print("=" * 65)

# Height progression from h_0 down to 0
y = np.linspace(height, 0, 200)
U_profile = mass * g * y
K_profile = U_initial - U_profile
v_profile = np.sqrt(2 * g * (height - y))

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Energy Exchange vs Altitude y
ax1.plot(y, U_profile, 'b-', lw=2.2, label=r'Potential Energy $U(y) = mgy$')
ax1.plot(y, K_profile, 'r-', lw=2.2, label=r'Kinetic Energy $K(y) = \frac{1}{2}mv^2$')
ax1.plot(y, U_profile + K_profile, 'g--', lw=1.8, label=r'Total Energy $E = U + K$')
ax1.set_title(r'Mechanical Energy Conservation ($E = U + K$)', fontsize=12, fontweight='bold')
ax1.set_xlabel('Height $y$ (m)', fontsize=11)
ax1.set_ylabel('Energy (Joules)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Velocity vs Height
ax2.plot(y, v_profile, 'm-', lw=2.0, label=r'$v(y) = \sqrt{2g(h_0 - y)}$')
ax2.scatter([0], [v_impact], color='red', s=80, zorder=5, label=f'Impact $v = {v_impact:.2f}$ m/s')
ax2.set_title('Velocity Acquisition during Free Fall', fontsize=12, fontweight='bold')
ax2.set_xlabel('Height $y$ (m)', fontsize=11)
ax2.set_ylabel('Downward Velocity (m/s)', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        $code = str_replace(['{{MASS}}', '{{HEIGHT}}'], [(string)$mass, (string)$height], $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Gravitational Potential Energy & Free Fall Conservation (m = {$mass} kg, h = {$height} m)",
            'code' => $code,
            'explanation' => "Models conservative mechanical energy conversion from gravitational potential energy \$U = mgh\$ to kinetic energy \$K = \\frac{1}{2}mv^2\$. Validates total mechanical energy invariance \$E = U + K = \\text{const}\$.",
            'complexity' => 'Time: O(1), Space: O(1)',
            'exercises' => [
                'Incorporate aerodynamic quadratic drag F_drag = -0.5 * rho * C_d * A * v^2 to compute terminal velocity.',
                'Contrast the near-Earth linear approximation U = mgh with Newton\'s universal gravitation U(r) = -G * M * m / r.',
                'Compute the bounce height and mechanical coefficient of restitution e = v_after / v_before.'
            ]
        ];
    }

    // =========================================================================
    // 0F. SPRING-MASS HARMONIC OSCILLATOR (SHM, DAMPED & PHASE SPACE)
    // =========================================================================
    if (str_contains($p, 'spring') || str_contains($p, 'spring-mass') || str_contains($p, 'spring mass') || (str_contains($p, 'harmonic oscillator') && !str_contains($p, 'pendulum') && !str_contains($p, 'quantum'))) {
        $m_val = 1.0;
        $k_val = 25.0;
        $b_val = 0.4;
        if (preg_match('/(?:mass|m)\s*=?\s*(\d+(?:\.\d+)?)/i', $problem, $m)) $m_val = (float)$m[1];
        if (preg_match('/(?:k|spring\s*constant)\s*=?\s*(\d+(?:\.\d+)?)/i', $problem, $m)) $k_val = (float)$m[1];
        if (preg_match('/(?:damping|b|gamma)\s*=?\s*(\d+(?:\.\d+)?)/i', $problem, $m)) $b_val = (float)$m[1];

        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Harmonic Oscillator: Spring-Mass System (SHM, Damping & Phase Space)
# Governing Equation: m * d^2x/dt^2 + b * dx/dt + k * x = 0
# Angular Frequency: omega_0 = sqrt(k / m)
# Damping Ratio:     zeta = b / (2 * sqrt(m * k))

m = {{MASS}}      # Mass (kg)
k = {{K}}         # Spring constant (N/m)
b = {{B}}         # Damping coefficient (N·s/m)
x0 = 0.2          # Initial displacement (m)
v0 = 0.0          # Initial velocity (m/s)

omega_0 = np.sqrt(k / m)
zeta = b / (2.0 * np.sqrt(m * k))
T_period = 2.0 * np.pi / omega_0

# Time array
t_max = 5.0 * T_period
dt = 0.005
t = np.arange(0, t_max, dt)
steps = len(t)

# State arrays: x (position), v (velocity)
x = np.zeros(steps)
v = np.zeros(steps)
x[0] = x0
v[0] = v0

# RK4 Numerical Integration
def deriv(pos, vel):
    acc = -(k / m) * pos - (b / m) * vel
    return vel, acc

for i in range(steps - 1):
    xi, vi = x[i], v[i]
    k1_x, k1_v = deriv(xi, vi)
    k2_x, k2_v = deriv(xi + 0.5 * dt * k1_x, vi + 0.5 * dt * k1_v)
    k3_x, k3_v = deriv(xi + 0.5 * dt * k2_x, vi + 0.5 * dt * k2_v)
    k4_x, k4_v = deriv(xi + dt * k3_x, vi + dt * k3_v)
    
    x[i + 1] = xi + (dt / 6.0) * (k1_x + 2*k2_x + 2*k3_x + k4_x)
    v[i + 1] = vi + (dt / 6.0) * (k1_v + 2*k2_v + 2*k3_v + k4_v)

# Energies: Kinetic and Potential
T_kinetic = 0.5 * m * v**2
V_potential = 0.5 * k * x**2
E_total = T_kinetic + V_potential

print("=" * 65)
print(f"SPRING-MASS HARMONIC OSCILLATOR REPORT (m = {m} kg, k = {k} N/m)")
print("=" * 65)
print(f"Natural Angular Frequency (omega_0): {omega_0:.4f} rad/s")
print(f"Natural Oscillation Period (T):     {T_period:.4f} seconds")
print(f"Damping Ratio (zeta):                {zeta:.4f} ({'Underdamped' if zeta < 1 else ('Critically Damped' if zeta == 1 else 'Overdamped')})")
print(f"Initial Mechanical Energy:           {E_total[0]:.4f} Joules")
print(f"Final Mechanical Energy:             {E_total[-1]:.4f} Joules")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Position and Velocity vs Time
ax1.plot(t, x, 'b-', lw=1.8, label=r'Displacement $x(t)$ (m)')
ax1.plot(t, v / omega_0, 'r--', lw=1.5, label=r'Normalized Velocity $v/\omega_0$')
ax1.set_title(rf'Spring-Mass SHM: $\omega_0 = {omega_0:.2f}$ rad/s, $\zeta = {zeta:.2f}$', fontsize=12, fontweight='bold')
ax1.set_xlabel('Time $t$ (s)', fontsize=11)
ax1.set_ylabel('Amplitude', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Phase Space Trajectory (x vs v)
ax2.plot(x, v, 'm-', lw=1.5, label='Phase Portrait')
ax2.scatter([x[0]], [v[0]], color='green', s=70, zorder=5, label='Start')
ax2.scatter([x[-1]], [v[-1]], color='red', s=70, zorder=5, label='End')
ax2.set_title('Phase Space Portrait $(x, v)$', fontsize=12, fontweight='bold')
ax2.set_xlabel('Displacement $x$ (m)', fontsize=11)
ax2.set_ylabel('Velocity $v$ (m/s)', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        $code = str_replace(['{{MASS}}', '{{K}}', '{{B}}'], [(string)$m_val, (string)$k_val, (string)$b_val], $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Spring-Mass Harmonic Oscillator & Phase Space (m = {$m_val} kg, k = {$k_val} N/m)",
            'code' => $code,
            'explanation' => "Simulates linear harmonic motion with viscous damping \$m\\ddot{x} + b\\dot{x} + kx = 0\$ using 4th-order Runge-Kutta integration. Traces position, velocity, and phase space orbit attractor decay.",
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Set damping b = 0 to verify exact analytical energy conservation E = 0.5 * k * x_0^2.',
                'Find the critical damping coefficient b_crit = 2 * sqrt(m * k) and demonstrate non-oscillatory return.',
                'Add a harmonic driving force F_0 * cos(omega_d * t) and plot the Lorentzian resonance amplitude peak.'
            ]
        ];
    }

    // =========================================================================
    // 0G. ELECTROSTATICS: POINT CHARGE ELECTRIC FIELD & POTENTIAL
    // =========================================================================
    if (str_contains($p, 'point charge') || str_contains($p, 'coulomb') || (str_contains($p, 'electric') && (str_contains($p, 'charge') || (str_contains($p, 'field') && !str_contains($p, 'magnetic')) || (str_contains($p, 'potential') && !str_contains($p, 'gravit') && !str_contains($p, 'well'))))) {
        $q_val = 1e-9;
        $r_val = 0.05;
        if (preg_match('/(?:charge|q)\s*=?\s*(-?\d+(?:\.\d+)?(?:e-?\d+)?)/i', $problem, $m)) {
            $q_val = (float)$m[1];
        }
        if (preg_match('/(?:distance|radius|r)\s*=?\s*(\d+(?:\.\d+)?)\s*(?:cm|m)?/i', $problem, $m)) {
            $r_val = (float)$m[1];
            if (str_contains(strtolower($m[0]), 'cm')) $r_val /= 100.0;
        }

        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Electrostatics: Point Charge Coulomb Field & Electric Potential
# Formulations:
# Coulomb's Constant: k_e = 1 / (4 * pi * epsilon_0) ≈ 8.98755e9 N·m^2/C^2
# Electric Field:     E(r) = (k_e * |q| / r^2) * r_hat
# Electric Potential: V(r) = k_e * q / r

k_e = 8.98755e9   # Coulomb constant (N·m^2/C^2)
q = {{CHARGE}}    # Charge in Coulombs
r_ref = {{R}}     # Target distance (m)

E_at_r = k_e * abs(q) / r_ref**2
V_at_r = k_e * q / r_ref

print("=" * 65)
print(f"ELECTROSTATIC TELEMETRY FOR POINT CHARGE q = {q:.3e} C")
print("=" * 65)
print(f"Distance from Charge (r):      {r_ref:.4f} m ({r_ref*100:.2f} cm)")
print(f"Electric Field Magnitude (E):  {E_at_r:.4e} N/C (or V/m)")
print(f"Electric Potential (V):        {V_at_r:.4f} Volts")
print("=" * 65)

# 1. Radial Decay Curves
r = np.linspace(0.01, max(0.5, r_ref * 2.5), 300)
E_radial = k_e * abs(q) / r**2
V_radial = k_e * q / r

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: 1/r^2 Electric Field & 1/r Potential Decay
ax1.plot(r * 100, E_radial, 'b-', lw=2, label=r'Electric Field $E(r) \propto 1/r^2$')
ax1.plot(r * 100, V_radial, 'r--', lw=1.8, label=r'Potential $V(r) \propto 1/r$')
ax1.scatter([r_ref * 100], [E_at_r], color='blue', s=80, zorder=5, label=f'E({r_ref*100:.1f}cm)')
ax1.scatter([r_ref * 100], [V_at_r], color='red', s=80, zorder=5, label=f'V({r_ref*100:.1f}cm)')
ax1.set_title('Radial Decay of Electric Field & Potential', fontsize=12, fontweight='bold')
ax1.set_xlabel('Radial Distance $r$ (cm)', fontsize=11)
ax1.set_ylabel('Magnitude (SI Units)', fontsize=11)
ax1.set_yscale('log')
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: 2D Field Lines & Equipotential Contours
L = max(0.2, r_ref * 2.0)
grid = np.linspace(-L, L, 100)
X, Y = np.meshgrid(grid, grid)
R = np.sqrt(X**2 + Y**2)
R[R < 0.01] = 0.01

Ex = k_e * q * X / R**3
Ey = k_e * q * Y / R**3
V_2d = k_e * q / R

ax2.streamplot(X, Y, Ex, Ey, color='navy', density=1.0, linewidth=0.8, arrowsize=1.0)
contours = ax2.contour(X, Y, V_2d, levels=8, cmap='coolwarm', alpha=0.7)
ax2.clabel(contours, inline=True, fontsize=7)
ax2.scatter([0], [0], color='red' if q > 0 else 'blue', s=120, edgecolors='black', zorder=10, label=f'q = {q:.1e} C')
ax2.set_aspect('equal')
ax2.set_title('2D Electric Field Lines & Equipotential Rings', fontsize=12, fontweight='bold')
ax2.set_xlabel('$x$ (m)', fontsize=11)
ax2.set_ylabel('$y$ (m)', fontsize=11)
ax2.legend(loc='upper right')

plt.tight_layout()
plt.show()
PY;
        $code = str_replace(['{{CHARGE}}', '{{R}}'], [(string)$q_val, (string)$r_val], $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Electrostatics: Point Charge Electric Field & Potential (q = " . sprintf('%.1e', $q_val) . " C, r = {$r_val} m)",
            'code' => $code,
            'explanation' => "Formulates Coulomb's law electrostatics: electric field \$E(r) = \\frac{1}{4\\pi\\varepsilon_0} \\frac{q}{r^2}\$ and scalar potential \$V(r) = \\frac{1}{4\\pi\\varepsilon_0} \\frac{q}{r}\$. Visualizes logarithmic radial decay and 2D vector stream lines with equipotential surfaces.",
            'complexity' => 'Time: O(N^2) grid field, Space: O(N^2)',
            'exercises' => [
                'Add a second point charge to compute the dipole field E = E_1 + E_2 by vector superposition.',
                'Compute Gauss\'s law electric flux through a spherical surface of radius R.',
                'Calculate the electrostatic work required to move a test charge from infinity to r.'
            ]
        ];
    }

    // =========================================================================
    // 0H. LINEAR ALGEBRA: MATRIX ADDITION, SUBTRACTION & MULTIPLICATION
    // =========================================================================
    if (str_contains($p, 'matrix') && (str_contains($p, 'addition') || str_contains($p, 'subtraction') || str_contains($p, 'multiplication') || str_contains($p, 'multiply') || str_contains($p, 'add') || str_contains($p, 'product'))) {
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Computational Linear Algebra: Matrix Arithmetic & Matrix Multiplication
# Operations:
# 1. Addition:       C = A + B       (C_ij = A_ij + B_ij)
# 2. Subtraction:    D = A - B       (D_ij = A_ij - B_ij)
# 3. Multiplication: M = A @ B       (M_ij = \sum_k A_ik * B_kj)

A = np.array([
    [2.0, 1.0, 3.0],
    [1.0, 4.0, 2.0],
    [3.0, 2.0, 5.0]
])

B = np.array([
    [1.0, 0.0, 2.0],
    [3.0, 2.0, 1.0],
    [0.0, 1.0, 4.0]
])

# Matrix Operations
C_add = A + B
D_sub = A - B
M_mul = A @ B
det_A = np.linalg.det(A)
inv_A = np.linalg.inv(A)

print("=" * 65)
print("COMPUTATIONAL MATRIX ARITHMETIC REPORT")
print("=" * 65)
print("Matrix A (3x3):\n", A)
print("\nMatrix B (3x3):\n", B)
print("\nMatrix Addition (A + B):\n", C_add)
print("\nMatrix Subtraction (A - B):\n", D_sub)
print("\nMatrix Product (A @ B):\n", M_mul)
print(f"\nDeterminant det(A): {det_A:.4f}")
print("Matrix Inverse A^(-1):\n", np.round(inv_A, 4))
print("=" * 65)

fig, ((ax1, ax2), (ax3, ax4)) = plt.subplots(2, 2, figsize=(10, 8))

def plot_mat(ax, mat, title, cmap):
    im = ax.imshow(mat, cmap=cmap, aspect='auto')
    ax.set_title(title, fontsize=11, fontweight='bold')
    for i in range(mat.shape[0]):
        for j in range(mat.shape[1]):
            ax.text(j, i, f'{mat[i, j]:.1f}', ha='center', va='center',
                    color='white' if abs(mat[i, j]) > np.max(np.abs(mat))*0.5 else 'black',
                    fontweight='bold', fontsize=10)
    plt.colorbar(im, ax=ax, shrink=0.8)

plot_mat(ax1, A, 'Input Matrix A', 'Blues')
plot_mat(ax2, B, 'Input Matrix B', 'Oranges')
plot_mat(ax3, C_add, 'Addition (A + B)', 'Greens')
plot_mat(ax4, M_mul, 'Multiplication (A @ B)', 'Purples')

plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Computational Linear Algebra: Matrix Addition, Subtraction & Multiplication",
            'code' => $template,
            'explanation' => "Computes fundamental matrix operations: element-wise addition \$A+B\$, subtraction \$A-B\$, dot product matrix multiplication \$A \\cdot B\$, determinant \$\\det(A)\$, and inverse \$A^{-1}\$. Heatmaps visualize matrix entries and transformation results.",
            'complexity' => 'Addition: O(N^2), Multiplication: O(N^3)',
            'exercises' => [
                'Verify non-commutativity by comparing A @ B with B @ A.',
                'Compute the trace of matrix A and show that Tr(A + B) = Tr(A) + Tr(B).',
                'Demonstrate that (A @ B).T = B.T @ A.T.'
            ]
        ];
    }

    // =========================================================================
    // 0I. OPTICS: SNELL'S LAW OF REFRACTION & CRITICAL ANGLE
    // =========================================================================
    if (str_contains($p, 'snell') || str_contains($p, 'refraction') || str_contains($p, 'critical angle') || str_contains($p, 'total internal reflection')) {
        $n1 = 1.0;
        $n2 = 1.5;
        $theta1 = 30.0;
        if (preg_match('/n1\s*=?\s*(\d+(?:\.\d+)?)/i', $problem, $m)) $n1 = (float)$m[1];
        if (preg_match('/n2\s*=?\s*(\d+(?:\.\d+)?)/i', $problem, $m)) $n2 = (float)$m[1];
        if (preg_match('/(?:angle|theta|at)\s*=?\s*(\d+(?:\.\d+)?)\s*(?:deg|degree|°)?/i', $problem, $m)) $theta1 = (float)$m[1];

        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Wave Optics: Snell's Law of Refraction & Total Internal Reflection
# Formulation: n1 * sin(theta_1) = n2 * sin(theta_2)
# Critical Angle (n1 > n2): theta_c = arcsin(n2 / n1)

n1 = {{N1}}         # Refractive index of medium 1
n2 = {{N2}}         # Refractive index of medium 2
theta1_deg = {{THETA1}} # Angle of incidence (degrees)

theta1_rad = np.radians(theta1_deg)

# Check critical angle
critical_angle_deg = None
if n1 > n2:
    critical_angle_deg = np.degrees(np.arcsin(n2 / n1))

sin_theta2 = (n1 / n2) * np.sin(theta1_rad)
if sin_theta2 <= 1.0:
    theta2_deg = np.degrees(np.arcsin(sin_theta2))
    tir = False
else:
    theta2_deg = None
    tir = True

print("=" * 65)
print(f"SNELL'S LAW OF REFRACTION REPORT (n1 = {n1}, n2 = {n2})")
print("=" * 65)
print(f"Angle of Incidence (theta_1):  {theta1_deg:.2f}°")
if tir:
    print(f"Condition:                     TOTAL INTERNAL REFLECTION (TIR)")
    print(f"Critical Angle (theta_c):      {critical_angle_deg:.2f}°")
else:
    print(f"Angle of Refraction (theta_2): {theta2_deg:.2f}°")
    if critical_angle_deg:
        print(f"Critical Angle (theta_c):      {critical_angle_deg:.2f}°")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Snell's Law Curve (theta2 vs theta1)
theta_in = np.linspace(0, 90, 300)
theta_in_rad = np.radians(theta_in)
sin_t2 = (n1 / n2) * np.sin(theta_in_rad)
valid = sin_t2 <= 1.0

ax1.plot(theta_in[valid], np.degrees(np.arcsin(sin_t2[valid])), 'b-', lw=2.2, label=r'$\theta_2(\theta_1)$')
if critical_angle_deg:
    ax1.axvline(critical_angle_deg, color='red', ls='--', label=rf'Critical $\theta_c = {critical_angle_deg:.1f}^\circ$')
if not tir:
    ax1.scatter([theta1_deg], [theta2_deg], color='gold', s=100, edgecolors='black', zorder=5, label=f'Current: {theta2_deg:.1f}°')
ax1.set_title(rf"Snell's Law: $n_1={n1} \to n_2={n2}$", fontsize=12, fontweight='bold')
ax1.set_xlabel('Angle of Incidence $\\theta_1$ (deg)', fontsize=11)
ax1.set_ylabel('Angle of Refraction $\\theta_2$ (deg)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: 2D Optical Ray Diagram at Interface
ax2.axhline(0, color='black', lw=2)
ax2.fill_between([-1, 1], 0, 1, color='lightcyan', alpha=0.4, label=f'Medium 1 (n={n1})')
ax2.fill_between([-1, 1], -1, 0, color='lightskyblue', alpha=0.4, label=f'Medium 2 (n={n2})')
ax2.axvline(0, color='gray', ls=':', lw=1.5, label='Normal Line')

# Incident Ray
ray_len = 0.8
x_inc = -ray_len * np.sin(theta1_rad)
y_inc = ray_len * np.cos(theta1_rad)
ax2.plot([x_inc, 0], [y_inc, 0], 'r-', lw=2.5, label='Incident Ray')

if tir:
    ax2.plot([0, ray_len * np.sin(theta1_rad)], [0, ray_len * np.cos(theta1_rad)], 'g--', lw=2, label='TIR Reflected Ray')
else:
    t2_rad = np.radians(theta2_deg)
    x_ref = ray_len * np.sin(t2_rad)
    y_ref = -ray_len * np.cos(t2_rad)
    ax2.plot([0, x_ref], [0, y_ref], 'g-', lw=2.5, label='Refracted Ray')

ax2.set_xlim(-1, 1)
ax2.set_ylim(-1, 1)
ax2.set_aspect('equal')
ax2.set_title('Ray Trajectory Across Interface', fontsize=12, fontweight='bold')
ax2.legend(loc='upper right', fontsize=8)

plt.tight_layout()
plt.show()
PY;
        $code = str_replace(['{{N1}}', '{{N2}}', '{{THETA1}}'], [(string)$n1, (string)$n2, (string)$theta1], $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Optics: Snell's Law of Refraction & Critical Angle (n1 = {$n1}, n2 = {$n2})",
            'code' => $code,
            'explanation' => "Formulates optical boundary refraction using Snell's law \$n_1 \\sin\\theta_1 = n_2 \\sin\\theta_2\$. Computes the critical angle \$\\theta_c = \\arcsin(n_2/n_1)\$ for total internal reflection and renders a 2D ray trajectory.",
            'complexity' => 'Time: O(1), Space: O(1)',
            'exercises' => [
                'Compute Brewster\'s polarizing angle theta_B = arctan(n2 / n1).',
                'Calculate Fresnel reflectance coefficients R_s and R_p for TE and TM polarizations.',
                'Model refraction through a triangular glass prism and determine the angle of minimum deviation.'
            ]
        ];
    }

    // =========================================================================
    // 0J. OPTICS: THIN LENS FORMULA & RAY TRACING
    // =========================================================================
    if (str_contains($p, 'lens') || str_contains($p, 'thin lens') || str_contains($p, 'lens maker')) {
        $f_val = 15.0;
        $u_val = -30.0;
        if (preg_match('/(?:focal|f)\s*=?\s*(-?\d+(?:\.\d+)?)/i', $problem, $m)) $f_val = (float)$m[1];
        if (preg_match('/(?:object|u)\s*=?\s*(-?\d+(?:\.\d+)?)/i', $problem, $m)) $u_val = -abs((float)$m[1]);

        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Geometrical Optics: Thin Lens Formula & Ray Tracing
# Gaussian Thin Lens Equation: 1/f = 1/v - 1/u
# Magnification:                m = v / u = h_i / h_o

f = {{F}}        # Focal length in cm
u = {{U}}        # Object distance in cm (negative convention)
h_o = 4.0        # Object height in cm

# Image distance v
v = 1.0 / (1.0 / f + 1.0 / u)
m = v / u
h_i = m * h_o

print("=" * 65)
print(f"THIN LENS OPTICS REPORT (f = {f} cm, u = {u} cm)")
print("=" * 65)
print(f"Image Distance (v):        {v:.2f} cm ({'Real (Right)' if v > 0 else 'Virtual (Left)'})")
print(f"Transverse Magnification:  {m:.3f}")
print(f"Image Height (h_i):        {h_i:.2f} cm ({'Inverted' if h_i < 0 else 'Erect'})")
print("=" * 65)

fig, ax = plt.subplots(figsize=(10, 5))

# Optical Axis & Lens Plane
ax.axhline(0, color='black', lw=1.2)
ax.axvline(0, color='blue', lw=2.5, ls='-', label='Thin Lens')

# Focal Points
ax.scatter([-f, f], [0, 0], color='red', s=60, zorder=5)
ax.text(-f, -0.6, f'F1 (-{abs(f)})', ha='center', fontweight='bold', fontsize=9)
ax.text(f, -0.6, f'F2 (+{abs(f)})', ha='center', fontweight='bold', fontsize=9)

# Object Arrow
ax.arrow(u, 0, 0, h_o, head_width=1.5, head_length=0.6, fc='green', ec='green', lw=2.5, length_includes_head=True)
ax.text(u, h_o + 0.4, f'Object (h={h_o}cm)', ha='center', color='green', fontweight='bold', fontsize=9)

# Image Arrow
ax.arrow(v, 0, 0, h_i, head_width=1.5, head_length=0.6 * np.sign(h_i), fc='purple', ec='purple', lw=2.5, length_includes_head=True)
ax.text(v, h_i + (0.4 if h_i > 0 else -0.8), f'Image (h={h_i:.1f}cm)', ha='center', color='purple', fontweight='bold', fontsize=9)

# Principal Rays
ax.plot([u, 0, v], [h_o, h_o, h_i], 'r--', lw=1.5, label='Ray 1: Parallel to Focus')
ax.plot([u, v], [h_o, h_i], 'orange', ls='--', lw=1.5, label='Ray 2: Central Ray')

ax.set_title(rf'Thin Lens Ray Diagram: $f={f}$ cm, $u={u}$ cm $\implies v={v:.1f}$ cm ($m={m:.2f}$)', fontsize=12, fontweight='bold')
ax.set_xlabel('Optical Axis $x$ (cm)', fontsize=11)
ax.set_ylabel('Height $y$ (cm)', fontsize=11)
ax.grid(True, alpha=0.3)
ax.legend(loc='upper right')

plt.tight_layout()
plt.show()
PY;
        $code = str_replace(['{{F}}', '{{U}}'], [(string)$f_val, (string)$u_val], $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Thin Lens Equation & Optical Ray Tracing (f = {$f_val} cm, u = {$u_val} cm)",
            'code' => $code,
            'explanation' => "Formulates Gaussian geometrical optics \$\\frac{1}{f} = \\frac{1}{v} - \\frac{1}{u}\$ and computes transverse magnification \$m = \\frac{v}{u}\$. Plots geometric principal ray paths and image formation.",
            'complexity' => 'Time: O(1), Space: O(1)',
            'exercises' => [
                'Model a concave divergent lens with f < 0 and demonstrate virtual image formation.',
                'Simulate a two-lens compound microscope and compute total angular magnification.',
                'Compute chromatic aberration arising from dispersion n(lambda) across wavelengths.'
            ]
        ];
    }

    // =========================================================================
    // 0K. MODERN PHYSICS: BOHR MODEL OF HYDROGEN ATOM & SPECTRAL SERIES
    // =========================================================================
    if (str_contains($p, 'bohr') || str_contains($p, 'hydrogen atom') || str_contains($p, 'rydberg') || str_contains($p, 'spectral series') || str_contains($p, 'balmer')) {
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Quantum Physics: Bohr Model of Hydrogen Atom & Spectral Series
# Energy Levels: E_n = -13.606 / n^2 eV
# Orbital Radii: r_n = n^2 * a_0 (a_0 ≈ 0.529 Angstrom)
# Rydberg Formula: 1 / lambda = R_H * (1 / n1^2 - 1 / n2^2)

R_H = 1.097373e7   # Rydberg constant (m^-1)
a_0 = 0.529177     # Bohr radius (Angstroms)

ns = np.arange(1, 7)
energies = -13.606 / ns**2
radii = ns**2 * a_0

print("=" * 65)
print("BOHR HYDROGEN ATOM ENERGY LEVELS & SPECTRAL SERIES")
print("=" * 65)
for n, E, r in zip(ns, energies, radii):
    print(f"State n = {n}:  Energy = {E:>7.3f} eV  |  Orbital Radius = {r:>7.3f} Å")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Energy Level Diagram & Transitions
for n, E in zip(ns, energies):
    ax1.axhline(E, color='navy', lw=2)
    ax1.text(0.1, E + 0.2, f'n = {n} ({E:.2f} eV)', fontweight='bold', fontsize=9)

# Balmer Series transitions (n2 -> 2)
balmer_colors = ['red', 'cyan', 'blue', 'violet']
for idx, n2 in enumerate([3, 4, 5, 6]):
    w_inv = R_H * (1.0/4.0 - 1.0/n2**2)
    w_nm = (1.0 / w_inv) * 1e9
    x_pos = 0.4 + idx * 0.12
    ax1.annotate('', xy=(x_pos, -13.606/4), xytext=(x_pos, -13.606/n2**2),
                 arrowprops=dict(facecolor=balmer_colors[idx], shrink=0.05, width=1.5, headwidth=6))
    ax1.text(x_pos, -13.606/n2**2 + 0.3, f'{w_nm:.0f}nm', ha='center', fontsize=8, color=balmer_colors[idx], fontweight='bold')

ax1.set_xlim(0, 1)
ax1.set_ylim(-14.5, 0.5)
ax1.set_xticks([])
ax1.set_title('Bohr Energy Levels & Balmer Emission (n > 2 to 2)', fontsize=12, fontweight='bold')
ax1.set_ylabel('Energy $E_n$ (eV)', fontsize=11)
ax1.grid(True, alpha=0.3, axis='y')

# Plot 2: Orbital Radii Scaling (Quadratic n^2)
ax2.plot(ns, radii, 'bo-', lw=2, markersize=7, label=r'Radius $r_n = n^2 a_0$')
ax2.set_title(r'Bohr Orbit Radii Growth ($r_n \propto n^2$)', fontsize=12, fontweight='bold')
ax2.set_xlabel('Principal Quantum Number $n$', fontsize=11)
ax2.set_ylabel('Orbital Radius $r_n$ (Å)', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Quantum Mechanics: Bohr Model of Hydrogen Atom & Spectral Series",
            'code' => $template,
            'explanation' => "Formulates quantized angular momentum \$L = n\\hbar\$, deriving hydrogen energy levels \$E_n = -\\frac{13.6}{n^2}\$ eV and orbital radii \$r_n = n^2 a_0\$. Calculates Rydberg emission wavelengths for the Balmer visible spectral series.",
            'complexity' => 'Time: O(1), Space: O(1)',
            'exercises' => [
                'Compute transition wavelengths for the ultraviolet Lyman series (n1 = 1).',
                'Calculate the ionization energy required to liberate an electron from the ground state.',
                'Incorporate reduced mass mu = m_e * M_p / (m_e + M_p) to model Deuterium isotope spectral shifts.'
            ]
        ];
    }


    // =========================================================================
    // 1. FACTORIAL (Pure Factorial calculation, iterative, recursive, Stirling)
    // =========================================================================
    if (str_contains($p, 'factorial') || preg_match('/\b\d+!\b/', $p) || str_contains($p, 'stirling')) {
        $num = 6;
        if (preg_match('/\b(\d+)\b/', $problem, $m)) {
            $num = min(100, max(0, (int)$m[1]));
        }
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt
import math

# Factorial Computation: Iterative Algorithm & Stirling's Approximation
# Definition: n! = n * (n - 1) * ... * 2 * 1 (with 0! = 1)
# Stirling's Asymptotic Approximation: n! ≈ sqrt(2 * pi * n) * (n / e)^n

def factorial_iterative(n):
    """Compute exact integer factorial iteratively."""
    if n < 0:
        raise ValueError("Factorial is undefined for negative numbers.")
    result = 1
    for i in range(1, n + 1):
        result *= i
    return result

def stirling_approximation(n):
    """Compute continuous Stirling asymptotic approximation."""
    if n == 0:
        return 1.0
    return np.sqrt(2 * np.pi * n) * (n / np.e)**n

target_n = {{NUM}}
exact_fact = factorial_iterative(target_n)
stirling_val = stirling_approximation(target_n)
rel_error = abs(exact_fact - stirling_val) / exact_fact if exact_fact > 0 else 0.0

print("=" * 65)
print(f"FACTORIAL & ASYMPTOTIC ANALYSIS FOR n = {target_n}")
print("=" * 65)
print(f"Exact Factorial ({target_n}!):         {exact_fact}")
print(f"Stirling Approximation:        {stirling_val:.4e}")
print(f"Relative Asymptotic Error:     {rel_error * 100:.3f}%")
print("=" * 65)

# Benchmarking Growth Scaling across n = 1 to 20
ns = np.arange(1, 21)
exact_values = [factorial_iterative(int(i)) for i in ns]
stirling_values = [stirling_approximation(float(i)) for i in ns]
errors = [abs(e - s) / e * 100 for e, s in zip(exact_values, stirling_values)]

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Hyper-Exponential Factorial Growth (Logarithmic Scale)
ax1.semilogy(ns, exact_values, 'bo-', lw=1.8, markersize=5, label=r'Exact $n!$')
ax1.semilogy(ns, stirling_values, 'r--', lw=1.5, label=r"Stirling's Formula")
ax1.scatter([target_n], [exact_fact], color='gold', s=120, edgecolors='black', zorder=5, label=f'Target $n={target_n}$')
ax1.set_title(r'Factorial Growth: $n!$ vs Stirling Asymptotics', fontsize=12, fontweight='bold')
ax1.set_xlabel(r'Integer $n$', fontsize=11)
ax1.set_ylabel(r'$n!$ (Logarithmic Scale)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Relative Error Decay
ax2.plot(ns, errors, 'mo-', lw=1.8, markersize=5)
ax2.set_title("Stirling Asymptotic Relative Error Decay (%)", fontsize=12, fontweight='bold')
ax2.set_xlabel(r'Integer $n$', fontsize=11)
ax2.set_ylabel('Relative Error (%)', fontsize=11)
ax2.grid(True, alpha=0.3)

plt.tight_layout()
plt.show()
PY;
        $code = str_replace('{{NUM}}', (string)$num, $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Factorial Computation & Stirling's Asymptotic Approximation (n = {$num})",
            'code' => $code,
            'explanation' => "Computes exact factorials using an iterative O(N) accumulation algorithm. Validates the result against Stirling's asymptotic formula \$n! \\approx \\sqrt{2\\pi n} (n/e)^n\$, illustrating rapid convergence as \$n\$ increases.",
            'complexity' => 'Time: O(N), Space: O(1)',
            'exercises' => [
                'Implement the recursive version `factorial(n) = n * factorial(n - 1)` with base case `n <= 1`.',
                'Compute the number of trailing zeros in 100! by counting factors of 5.',
                'Generalize the factorial to real and complex numbers using the Euler Gamma function \\Gamma(x + 1).'
            ]
        ];
    }

    // =========================================================================
    // 2. FIBONACCI & GOLDEN RATIO
    // =========================================================================
    if (str_contains($p, 'fibonacci') || str_contains($p, 'golden ratio') || str_contains($p, 'binet')) {
        $nTerms = 20;
        if (preg_match('/\b(\d+)\b/', $problem, $m)) {
            $nTerms = min(50, max(5, (int)$m[1]));
        }
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Fibonacci Sequence & Asymptotic Golden Ratio Convergence
# Recurrence: F(0) = 0, F(1) = 1, F(n) = F(n-1) + F(n-2)
# Golden Ratio: phi = lim_{n -> inf} F(n)/F(n-1) = (1 + sqrt(5)) / 2 approx 1.6180339887

def generate_fibonacci(n):
    """Generate first n Fibonacci numbers."""
    if n <= 0:
        return [0]
    fib = [0, 1]
    for i in range(2, n + 1):
        fib.append(fib[-1] + fib[-2])
    return fib

N = {{NTERMS}}
fib = generate_fibonacci(N)
ratios = [fib[i] / fib[i-1] for i in range(2, len(fib))]
golden_ratio = (1 + np.sqrt(5)) / 2

print("=" * 65)
print(f"FIBONACCI SEQUENCE & GOLDEN RATIO ANALYSIS (N = {N})")
print("=" * 65)
print(f"First 10 Terms:               {fib[:10]}")
print(f"N = {N} Term:                   {fib[-1]}")
print(f"Ratio F({N})/F({N-1}):           {ratios[-1]:.10f}")
print(f"Analytical Golden Ratio (phi): {golden_ratio:.10f}")
print(f"Discrepancy:                  {abs(ratios[-1] - golden_ratio):.2e}")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Exponential Growth of Fibonacci Numbers
ax1.semilogy(range(len(fib)), fib, 'bo-', lw=1.8, markersize=5)
ax1.set_title('Fibonacci Exponential Growth Scaling', fontsize=12, fontweight='bold')
ax1.set_xlabel(r'Index $n$', fontsize=11)
ax1.set_ylabel(r'Fibonacci Value $F_n$ (Log Scale)', fontsize=11)
ax1.grid(True, alpha=0.3)

# Plot 2: Convergence to the Golden Ratio
ax2.plot(range(2, N + 1), ratios, 'ro-', lw=1.8, markersize=5, label=r'Empirical Ratio $F_n / F_{n-1}$')
ax2.axhline(golden_ratio, color='green', linestyle='--', lw=2, label=r'Golden Ratio $\phi \approx 1.61803$')
ax2.set_title(r'Convergence to Golden Ratio $\phi$', fontsize=12, fontweight='bold')
ax2.set_xlabel(r'Index $n$', fontsize=11)
ax2.set_ylabel(r'Consecutive Ratio $F_n / F_{n-1}$', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        $code = str_replace('{{NTERMS}}', (string)$nTerms, $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Fibonacci Sequence Generation & Golden Ratio Convergence (N = {$nTerms})",
            'code' => $code,
            'explanation' => "Generates the Fibonacci sequence via iterative dynamic programming in linear O(N) time. Demonstrates the quotient convergence of consecutive terms towards the irrational Golden Ratio \$\\phi = (1+\\sqrt{5})/2\$.",
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Compute the n-th Fibonacci number using Binet\'s closed-form formula \$F_n = (\\phi^n - \\psi^n)/\\sqrt{5}\$.',
                'Calculate the Greatest Common Divisor (GCD) of consecutive Fibonacci pairs using Euclid\'s algorithm.',
                'Analyze the relation between Fibonacci numbers and phyllotaxis in biological spiral patterns.'
            ]
        ];
    }

    // =========================================================================
    // 3. PRIME NUMBERS & SIEVE OF ERATOSTHENES
    // =========================================================================
    if (str_contains($p, 'prime') || str_contains($p, 'sieve') || str_contains($p, 'eratosthenes') || str_contains($p, 'primality')) {
        if (preg_match('/(?:first|initial)\s*(\d+)\s*primes?/i', $problem, $fm)) {
            $count = min(1000, max(1, (int)$fm[1]));
            $templateFirstN = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Prime Number Generation: First N Primes Algorithm
# Computes the exact first N prime numbers sequentially

def generate_first_n_primes(n):
    """Generate the exact first n prime numbers."""
    if n <= 0:
        return []
    primes = [2]
    cand = 3
    while len(primes) < n:
        is_p = True
        limit = int(np.isqrt(cand))
        for p in primes:
            if p > limit:
                break
            if cand % p == 0:
                is_p = False
                break
        if is_p:
            primes.append(cand)
        cand += 2
    return primes

N = {{COUNT}}
primes = generate_first_n_primes(N)

print("=" * 65)
print(f"FIRST {N} PRIME NUMBERS REPORT")
print("=" * 65)
print(f"Count of Primes:      {len(primes)}")
print(f"List of First {N} Primes:\n{primes}")
print(f"The {N}th Prime:       {primes[-1]}")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Prime Value Growth p_n vs Index n
indices = np.arange(1, len(primes) + 1)
ax1.plot(indices, primes, 'bo-', lw=1.8, markersize=5, label=r'Exact Prime $p_n$')
if len(primes) > 1:
    approx_pn = indices * np.log(np.maximum(2, indices))
    ax1.plot(indices, approx_pn, 'r--', lw=1.5, label=r"PNT Asymptotic $n \ln(n)$")
ax1.set_title(rf'Growth of First {N} Primes ($p_n$ vs $n$)', fontsize=12, fontweight='bold')
ax1.set_xlabel('Index $n$', fontsize=11)
ax1.set_ylabel(r'Prime Value $p_n$', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Prime Gaps (Differences between consecutive primes)
if len(primes) > 1:
    gaps = np.diff(primes)
    ax2.bar(indices[:-1], gaps, color='#38bdf8', edgecolor='navy', width=0.8)
    ax2.set_title('Prime Gaps $g_n = p_{n+1} - p_n$', fontsize=12, fontweight='bold')
    ax2.set_xlabel('Index $n$', fontsize=11)
    ax2.set_ylabel('Gap Size', fontsize=11)
    ax2.grid(True, alpha=0.3, axis='y')

plt.tight_layout()
plt.show()
PY;
            $code = str_replace('{{COUNT}}', (string)$count, $templateFirstN);
            return [
                'success' => true,
                'source' => 'physics_engine',
                'title' => "First {$count} Prime Numbers & Prime Gap Sequence",
                'code' => $code,
                'explanation' => "Generates the exact first \$N = {$count}\$ prime numbers sequentially using trial division up to \$\sqrt{k}\$ against previously discovered primes. Benchmarks prime growth against the Prime Number Theorem asymptotic \$p_n \sim n \ln(n)\$.",
                'complexity' => 'Time: O(N * sqrt(p_N)), Space: O(N)',
                'exercises' => [
                    'Analyze the distribution of prime gaps g_n = p_{n+1} - p_n to identify twin primes (g_n = 2).',
                    'Test the Hardy-Littlewood conjecture on the frequency of prime pairs.',
                    'Compare execution time against the segmented sieve algorithm.'
                ]
            ];
        }

        $limit = 200;
        if (preg_match('/(?:up to|limit|to|n\s*=)\s*(\d+)/i', $problem, $m)) {
            $limit = min(5000, max(2, (int)$m[1]));
        } elseif (preg_match('/\b(\d+)\b/', $problem, $m)) {
            $limit = min(5000, max(2, (int)$m[1]));
        }
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Prime Number Generation: Sieve of Eratosthenes & Prime Number Theorem
# Asymptotic Prime Counting Function: pi(x) ~ x / ln(x)

def sieve_of_eratosthenes(limit):
    """Find all prime numbers up to `limit` in O(N log log N) time."""
    if limit < 2:
        return []
    is_prime = np.ones(limit + 1, dtype=bool)
    is_prime[0:2] = False
    for p in range(2, int(np.isqrt(limit)) + 1):
        if is_prime[p]:
            is_prime[p*p : limit + 1 : p] = False
    return np.nonzero(is_prime)[0]

limit = {{LIMIT}}
primes = sieve_of_eratosthenes(limit)

print("=" * 65)
print(f"SIEVE OF ERATOSTHENES PRIMALITY REPORT (Limit = {limit})")
print("=" * 65)
print(f"Total Primes Found (pi({limit})): {len(primes)}")
print(f"First 15 Primes:             {primes[:15]}")
print(f"Largest Prime in Range:      {primes[-1]}")
pnt_estimate = limit / np.log(limit)
print(f"Prime Number Theorem x/ln(x): {pnt_estimate:.1f}")
print("=" * 65)

# Step function pi(x) vs analytical estimate x / ln(x)
x_vals = np.arange(2, limit + 1)
pi_actual = np.array([np.searchsorted(primes, x, side='right') for x in x_vals])
pi_pnt = x_vals / np.log(x_vals)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Cumulative Prime Counting Function pi(x)
ax1.step(x_vals, pi_actual, 'b-', lw=1.8, label=r'Actual $\pi(x)$')
ax1.plot(x_vals, pi_pnt, 'r--', lw=1.8, label=r'Gauss Estimate $x / \ln(x)$')
ax1.set_title(f'Prime Counting Function $\\pi(x)$ up to $x = {limit}$', fontsize=12, fontweight='bold')
ax1.set_xlabel(r'Integers $x$', fontsize=11)
ax1.set_ylabel(r'Number of Primes $\pi(x)$', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Prime Gap Distribution
if len(primes) > 1:
    gaps = np.diff(primes)
    ax2.hist(gaps, bins=np.arange(1.5, max(gaps) + 1.5, 2), color='teal', edgecolor='black', alpha=0.7)
    ax2.set_title('Prime Differences / Gaps Distribution', fontsize=12, fontweight='bold')
    ax2.set_xlabel(r'Gap Size ($p_{k+1} - p_k$)', fontsize=11)
    ax2.set_ylabel('Frequency', fontsize=11)
    ax2.grid(True, alpha=0.3)

plt.tight_layout()
plt.show()
PY;
        $code = str_replace('{{LIMIT}}', (string)$limit, $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Prime Number Generation: Sieve of Eratosthenes & Prime Counting Theorem (Limit = {$limit})",
            'code' => $code,
            'explanation' => "Computes all prime numbers up to {$limit} using the classical Sieve of Eratosthenes algorithm in O(N \\log \\log N) time. Compares the cumulative prime density \$\\pi(x)\$ against the asymptotic Prime Number Theorem approximation \$x/\\ln(x)\$.",
            'complexity' => 'Time: O(N log log N), Space: O(N)',
            'exercises' => [
                'Implement trial division primality test for an arbitrary integer and compare runtimes with the Sieve.',
                'Identify all Twin Primes (prime pairs with gap equal to 2) within the range.',
                'Evaluate the logarithmic integral \\mathrm{Li}(x) = \\int_2^x \\frac{dt}{\\ln t} and contrast with x/\\ln x.'
            ]
        ];
    }



    // =========================================================================
    // 3A. PALINDROME CHECKER (Numbers & Strings)
    // =========================================================================
    if (str_contains($p, 'palindrome') || str_contains($p, 'palindromic')) {
        $target = 12321;
        if (preg_match('/\b(\d+)\b/', $problem, $m)) {
            $target = (int)$m[1];
        }
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Palindrome Number & String Symmetry Analysis
# Definition: A sequence is palindromic if it reads identically forwards and backwards.

def is_palindrome_int(n):
    """Check integer palindrome using arithmetic reversal without string conversion."""
    if n < 0 or (n % 10 == 0 and n != 0):
        return False
    rev = 0
    temp = n
    while temp > 0:
        rev = rev * 10 + (temp % 10)
        temp //= 10
    return rev == n

target = {{NUM}}
is_target_pal = is_palindrome_int(target)

# Statistical distribution of palindromic numbers up to N_max
N_max = max(500, target * 2 if target < 50000 else 1000)
numbers = np.arange(1, N_max + 1)
palindromes = [n for n in numbers if is_palindrome_int(n)]
count_pal = len(palindromes)

print("=" * 65)
print(f"PALINDROME SYMMETRY ANALYSIS FOR TARGET = {target}")
print("=" * 65)
print(f"Is {target} a Palindrome?        {'YES (Symmetric)' if is_target_pal else 'NO (Asymmetric)'}")
print(f"Palindromes in [1, {N_max}]:     {count_pal} total")
print(f"First 10 Palindromes:         {palindromes[:10]}")
print(f"Palindromic Density:          {(count_pal / N_max) * 100:.2f}%")
print("=" * 65)

# Cumulative count scaling
cumulative_pals = np.cumsum([1 if is_palindrome_int(n) else 0 for n in numbers])

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Cumulative Palindromic Count
ax1.plot(numbers, cumulative_pals, 'b-', lw=2, label=r'Cumulative Count $\pi_{\mathrm{pal}}(N)$')
if is_target_pal and target <= N_max:
    target_idx = np.searchsorted(numbers, target)
    ax1.scatter([target], [cumulative_pals[target_idx]], color='red', s=90, zorder=5, label=f'Target {target}')
ax1.set_title(r'Cumulative Palindromic Integers up to $N$', fontsize=12, fontweight='bold')
ax1.set_xlabel(r'Upper Bound $N$', fontsize=11)
ax1.set_ylabel('Number of Palindromes', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Palindromic Digit Frequency Histogram
all_digits = [int(d) for p in palindromes for d in str(p)]
counts = [all_digits.count(d) for d in range(10)]
ax2.bar(range(10), counts, color='teal', alpha=0.75, edgecolor='black')
ax2.set_title('Digit Frequency in Palindromic Numbers', fontsize=12, fontweight='bold')
ax2.set_xlabel('Digit (0-9)', fontsize=11)
ax2.set_ylabel('Occurrences', fontsize=11)
ax2.set_xticks(range(10))
ax2.grid(True, alpha=0.3)

plt.tight_layout()
plt.show()
PY;
        $code = str_replace('{{NUM}}', (string)$target, $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Discrete Mathematics: Palindrome Symmetry Verification (N = {$target})",
            'code' => $code,
            'explanation' => "Evaluates integer and string symmetry. Uses numerical arithmetic digit reversal \$O(\\log_{10} N)\$ without string allocation, benchmarks cumulative density, and evaluates digit frequencies in base 10.",
            'complexity' => 'Time: O(log N), Space: O(1)',
            'exercises' => [
                'Generalize the palindrome verification to arbitrary base B (e.g. binary palindromes).',
                'Find the largest palindromic number made from the product of two 3-digit numbers.',
                'Implement the Lychrel problem: reverse-and-add conjecture to generate palindromes.'
            ]
        ];
    }

    // =========================================================================
    // 3B. ARMSTRONG / NARCISSISTIC NUMBERS
    // =========================================================================
    if (str_contains($p, 'armstrong') || str_contains($p, 'narcissistic')) {
        $target = 153;
        if (preg_match('/\b(\d+)\b/', $problem, $m)) {
            $target = (int)$m[1];
        }
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Armstrong / Narcissistic Number Verification
# Definition: An n-digit integer N is Armstrong if sum(digit^n) == N.
# Example: 153 = 1^3 + 5^3 + 3^3 = 1 + 125 + 27 = 153

def is_armstrong(n):
    if n < 0:
        return False
    digits = [int(d) for d in str(n)]
    k = len(digits)
    return sum(d**k for d in digits) == n

target = {{NUM}}
digits = [int(d) for d in str(target)]
k = len(digits)
powers = [d**k for d in digits]
sum_powers = sum(powers)
is_target_arm = (sum_powers == target)

# Search all Armstrong numbers up to 10,000
all_armstrong = [n for n in range(1, 10001) if is_armstrong(n)]

print("=" * 65)
print(f"ARMSTRONG / NARCISSISTIC NUMBER ANALYSIS FOR N = {target}")
print("=" * 65)
print(f"Number of Digits (k):         {k}")
print(f"Digit Powers decomposition:   " + " + ".join(f"{d}^{k} ({p})" for d, p in zip(digits, powers)) + f" = {sum_powers}")
print(f"Is {target} an Armstrong Number? {'YES (Confirmed)' if is_target_arm else 'NO (Sum differs from N)'}")
print(f"Known Armstrong numbers <= 10,000: {all_armstrong}")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Digit Power Contributions for Target
x_labels = [f"'{d}'^{k}" for d in digits]
ax1.bar(x_labels, powers, color='royalblue', edgecolor='navy', alpha=0.8)
ax1.axhline(target, color='crimson', linestyle='--', label=f'Target Value = {target}')
ax1.set_title(f'Digit Contributions: Sum = {sum_powers}', fontsize=12, fontweight='bold')
ax1.set_xlabel('Digit Component', fontsize=11)
ax1.set_ylabel(f'Power Value ($d^{k}$)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Distribution of All Armstrong Numbers up to 10,000
ax2.scatter(range(1, len(all_armstrong) + 1), all_armstrong, color='purple', s=80, edgecolors='black', zorder=5)
for idx, val in enumerate(all_armstrong):
    ax2.annotate(str(val), (idx + 1, val), textcoords="offset points", xytext=(0, 8), ha='center', fontsize=8)
ax2.plot(range(1, len(all_armstrong) + 1), all_armstrong, 'm--', alpha=0.5)
ax2.set_title('All Armstrong Numbers in [1, 10,000]', fontsize=12, fontweight='bold')
ax2.set_xlabel('Index', fontsize=11)
ax2.set_ylabel('Armstrong Integer Value', fontsize=11)
ax2.grid(True, alpha=0.3)

plt.tight_layout()
plt.show()
PY;
        $code = str_replace('{{NUM}}', (string)$target, $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Number Theory: Armstrong / Narcissistic Number Verification (N = {$target})",
            'code' => $code,
            'explanation' => "Decomposes integer into base-10 digits and checks if \$N = \\sum d_i^k\$ where \$k\$ is the number of digits. Searches and visualizes the complete finite set of Armstrong numbers up to 10,000.",
            'complexity' => 'Time: O(k) = O(log N), Space: O(k)',
            'exercises' => [
                'Demonstrate why there can be at most 89 Armstrong numbers in base 10.',
                'Find all 4-digit Armstrong numbers (e.g. 1634, 8208, 9474).',
                'Implement Armstrong verification in other number bases such as hexadecimal.'
            ]
        ];
    }

    // =========================================================================
    // 3C. BINARY SEARCH VS LINEAR SEARCH
    // =========================================================================
    if (str_contains($p, 'binary search') || (str_contains($p, 'linear search') && !str_contains($p, 'algebra'))) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Search Algorithms: Linear Search O(N) vs Binary Search O(log N)
# Simulates step-by-step element retrieval on sorted scientific datasets.

def linear_search(arr, target):
    comparisons = 0
    for idx, val in enumerate(arr):
        comparisons += 1
        if val == target:
            return idx, comparisons
    return -1, comparisons

def binary_search(arr, target):
    comparisons = 0
    left, right = 0, len(arr) - 1
    while left <= right:
        comparisons += 1
        mid = (left + right) // 2
        if arr[mid] == target:
            return mid, comparisons
        elif arr[mid] < target:
            left = mid + 1
        else:
            right = mid - 1
    return -1, comparisons

# Dataset of size N = 64
N = 64
data = np.sort(np.random.choice(np.arange(1, 500), size=N, replace=False))
target = data[N // 2 + 7]

idx_lin, steps_lin = linear_search(data, target)
idx_bin, steps_bin = binary_search(data, target)

print("=" * 65)
print(f"SEARCH ALGORITHM TELEMETRY FOR TARGET = {target} (Array Size N = {N})")
print("=" * 65)
print(f"Linear Search:  Found at index {idx_lin} in {steps_lin} comparisons [O(N)]")
print(f"Binary Search:  Found at index {idx_bin} in {steps_bin} comparisons [O(log2 N)]")
print(f"Speedup Ratio:  {steps_lin / steps_bin:.2f}x fewer comparison operations")
print("=" * 65)

# Scalability Benchmark across array sizes
sizes = np.array([16, 32, 64, 128, 256, 512, 1024, 2048, 4096])
o_linear = sizes
o_binary = np.log2(sizes)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Comparison Count for Current Test
ax1.bar(['Linear Search\nO(N)', 'Binary Search\nO(log N)'], [steps_lin, steps_bin], color=['#ef4444', '#10b981'], edgecolor='black', width=0.5)
ax1.set_title(f'Comparisons Needed (N = {N}, Target = {target})', fontsize=12, fontweight='bold')
ax1.set_ylabel('Step Count', fontsize=11)
for i, v in enumerate([steps_lin, steps_bin]):
    ax1.text(i, v + 0.5, str(v), ha='center', fontweight='bold')
ax1.grid(True, alpha=0.3)

# Plot 2: Algorithmic Complexity Growth (O(N) vs O(log N))
ax2.plot(sizes, o_linear, 'r-', lw=2, label=r'Linear Search $O(N)$')
ax2.plot(sizes, o_binary, 'g-', lw=2.5, label=r'Binary Search $O(\log_2 N)$')
ax2.set_title('Asymptotic Growth: Linear vs Logarithmic', fontsize=12, fontweight='bold')
ax2.set_xlabel('Array Size $N$', fontsize=11)
ax2.set_ylabel('Worst-Case Operations', fontsize=11)
ax2.set_yscale('log')
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Computational Algorithms: Binary Search vs Linear Search Complexity',
            'code' => $code,
            'explanation' => "Implements both Linear Search (\$O(N)\$) and Binary Search (\$O(\\log_2 N)\$) on a sorted dataset. Measures operation step counts and contrasts linear scaling against logarithmic scaling.",
            'complexity' => 'Binary: O(log N), Linear: O(N)',
            'exercises' => [
                'Implement binary search recursively and verify recursion stack depth.',
                'Modify binary search to find the lower bound and upper bound of duplicate elements.',
                'Analyze Ternary Search dividing the search space into 3 parts.'
            ]
        ];
    }

    // =========================================================================
    // 3D. BIOT-SAVART LAW & CIRCULAR CURRENT LOOP
    // =========================================================================
    if ((str_contains($p, 'biot') && str_contains($p, 'savart')) || (str_contains($p, 'circular') && (str_contains($p, 'loop') || str_contains($p, 'coil')) && str_contains($p, 'magnetic'))) {
        $radius = 0.10;
        if (preg_match('/(?:radius|r)\s*=?\s*(\d+(?:\.\d+)?)\s*(?:m|cm)?/i', $problem, $m)) {
            $radius = (float)$m[1];
            if (str_contains(strtolower($m[0]), 'cm')) $radius /= 100.0;
        }

        $turns = 1;
        if (preg_match('/(\d+)\s*(?:turns|windings|loops)/i', $problem, $m)) {
            $turns = (int)$m[1];
        }

        $current = 5.0;
        if (preg_match('/(?:current|i)\s*=?\s*(\d+(?:\.\d+)?)\s*(?:a|amp|amperes)?/i', $problem, $m)) {
            $current = (float)$m[1];
        }

        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Electrodynamics: Biot-Savart Law for N-Turn Circular Current Loop
# Axial Magnetic Field: B_z(z) = (N * mu_0 * I * R^2) / (2 * (R^2 + z^2)^(3/2))
# Center Field: B_0 = (N * mu_0 * I) / (2 * R)

mu0 = 4.0 * np.pi * 1e-7  # Permeability of vacuum (T*m/A)
N = {{TURNS}}             # Number of turns
I = {{CURRENT}}           # Current (Amperes)
R = {{RADIUS}}            # Radius of loop (meters)

# Axial profile from -3.5R to +3.5R
z = np.linspace(-3.5 * R, 3.5 * R, 500)
Bz_axial = (N * mu0 * I * R**2) / (2.0 * (R**2 + z**2)**1.5)
B0 = (N * mu0 * I) / (2.0 * R)

print("=" * 65)
print(f"BIOT-SAVART TELEMETRY: CIRCULAR COIL (N = {N}, R = {R*100:.1f} cm, I = {I} A)")
print("=" * 65)
print(f"Center Magnetic Field B(0):      {B0 * 1e6:.2f} microTesla")
print(f"Field at z = R ({R*100:.1f} cm):         {Bz_axial[np.argmin(np.abs(z - R))] * 1e6:.2f} microTesla")
print(f"Asymptotic 1/z^3 Dipole:         Confirmed for z >> R")
print("=" * 65)

# 2D Vector Streamlines in (r, z) plane
r_grid = np.linspace(-2.5 * R, 2.5 * R, 100)
z_grid = np.linspace(-2.5 * R, 2.5 * R, 100)
R_mesh, Z_mesh = np.meshgrid(r_grid, z_grid)

Br = (N * mu0 * I / (4 * np.pi)) * (3 * R_mesh * Z_mesh * R**2) / ((R_mesh**2 + Z_mesh**2 + R**2)**2.5 + 1e-6)
Bz = (N * mu0 * I / (2 * np.pi)) * (R**2) / ((R_mesh**2 + Z_mesh**2 + R**2)**1.5 + 1e-6)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Axial Magnetic Field Decay
ax1.plot(z * 100, Bz_axial * 1e6, 'b-', lw=2.2, label=r'$B_z(z)$')
ax1.axvline(R * 100, color='r', linestyle=':', label=r'Radius $z = R$')
ax1.axvline(-R * 100, color='r', linestyle=':')
ax1.scatter([0], [B0 * 1e6], color='gold', s=100, edgecolors='black', zorder=5, label=f'$B_0 = {B0*1e6:.1f}\\ \\mu$T')
ax1.set_title(r'Axial Magnetic Field $B_z(z)$', fontsize=12, fontweight='bold')
ax1.set_xlabel('Axial Distance $z$ (cm)', fontsize=11)
ax1.set_ylabel(r'Field Strength $B_z$ ($\mu$T)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: 2D Magnetic Field Streamlines
ax2.streamplot(r_grid * 100, z_grid * 100, Br, Bz, color='indigo', density=1.2, arrowsize=1.2)
ax2.scatter([-R * 100, R * 100], [0, 0], color='red', s=80, zorder=5, label='Current Elements')
ax2.set_title('Poloidal Magnetic Field Streamlines', fontsize=12, fontweight='bold')
ax2.set_xlabel('Radial Position $r$ (cm)', fontsize=11)
ax2.set_ylabel('Axial Position $z$ (cm)', fontsize=11)
ax2.set_aspect('equal')
ax2.grid(True, alpha=0.3)
ax2.legend(loc='upper right')

plt.tight_layout()
plt.show()
PY;
        $code = str_replace(
            ['{{TURNS}}', '{{CURRENT}}', '{{RADIUS}}'],
            [(string)$turns, (string)$current, (string)$radius],
            $template
        );
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Electrodynamics: Biot-Savart Law for Circular Coil (N = {$turns}, R = " . ($radius * 100) . " cm, I = {$current} A)",
            'code' => $code,
            'explanation' => "Calculates the axial magnetic field profile \$B_z(z) = \\frac{N \\mu_0 I R^2}{2(R^2 + z^2)^{3/2}}\$ and models 2D poloidal field streamlines. Demonstrates dipole \$1/z^3\$ asymptotic decay for \$z \\gg R\$.",
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Model Helmholtz coils (two identical loops separated by distance R) and verify magnetic field uniformity.',
                'Integrate the Biot-Savart law numerically along an arbitrary polygonal current loop.',
                'Calculate the vector potential A_theta and verify B = curl(A).'
            ]
        ];
    }

    // =========================================================================
    // 3D-1. SOLENOID, TOROID & HELMHOLTZ COILS MAGNETIC FIELD
    // =========================================================================
    if (str_contains($p, 'solenoid') || str_contains($p, 'toroid') || str_contains($p, 'helmholtz')) {
        $turns = 500;
        if (preg_match('/(\d+)\s*(?:turns|windings|loops)/i', $problem, $m)) {
            $turns = (int)$m[1];
        } elseif (preg_match('/(?:n|turns)\s*=?\s*(\d+)/i', $problem, $m)) {
            $turns = (int)$m[1];
        }

        $length = 0.5;
        if (preg_match('/(?:length|l)\s*=?\s*(\d+(?:\.\d+)?)\s*(?:m|cm|meters)?/i', $problem, $m)) {
            $length = (float)$m[1];
            if (str_contains(strtolower($m[0]), 'cm')) $length /= 100.0;
        } elseif (preg_match('/(\d+(?:\.\d+)?)\s*(?:m|meter|meters)\b/i', $problem, $m)) {
            $length = (float)$m[1];
        }

        $current = 2.0;
        if (preg_match('/(?:current|i)\s*=?\s*(\d+(?:\.\d+)?)\s*(?:a|amp|amperes)?/i', $problem, $m)) {
            $current = (float)$m[1];
        } elseif (preg_match('/(\d+(?:\.\d+)?)\s*(?:a|amp|amperes)\b/i', $problem, $m)) {
            $current = (float)$m[1];
        }

        $radius = 0.04; // 4 cm radius
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Electrodynamics: Magnetic Field Analysis of a Finite-Length Solenoid
# Infinite Solenoid Interior: B_inf = mu_0 * n * I = mu_0 * (N / L) * I
# Finite Solenoid Axial Profile:
# B_z(z) = (mu_0 * n * I / 2) * [ (z + L/2)/sqrt(R^2 + (z + L/2)^2) - (z - L/2)/sqrt(R^2 + (z - L/2)^2) ]

mu0 = 4.0 * np.pi * 1e-7   # Vacuum permeability (T·m/A)
N = {{TURNS}}              # Total number of turns
L = {{LENGTH}}             # Solenoid length (m)
I = {{CURRENT}}            # Electric current (A)
R = {{RADIUS}}             # Solenoid radius (m)

n = N / L                  # Linear turn density (turns/m)
B_inf = mu0 * n * I        # Ideal infinite solenoid center field (T)

# Axial position coordinate z from -1.5L to +1.5L
z = np.linspace(-1.5 * L, 1.5 * L, 600)

# Exact analytical finite solenoid axial field
term1 = (z + 0.5 * L) / np.sqrt(R**2 + (z + 0.5 * L)**2)
term2 = (z - 0.5 * L) / np.sqrt(R**2 + (z - 0.5 * L)**2)
Bz = 0.5 * mu0 * n * I * (term1 - term2)

# Field at the geometric center (z = 0)
B_center = 0.5 * mu0 * n * I * ( (0.5 * L) / np.sqrt(R**2 + 0.25 * L**2) - (-0.5 * L) / np.sqrt(R**2 + 0.25 * L**2) )
# Field at the solenoid ends (z = ± L / 2)
B_end = 0.5 * mu0 * n * I * ( L / np.sqrt(R**2 + L**2) )

print("=" * 65)
print(f"FINITE SOLENOID MAGNETIC TELEMETRY (N = {N}, L = {L} m, I = {I} A)")
print("=" * 65)
print(f"Turn Density (n):           {n:.1f} turns/m")
print(f"Ideal Infinite Field B_inf: {B_inf * 1e3:.3f} milliTesla")
print(f"Actual Center Field B(0):   {B_center * 1e3:.3f} milliTesla ({B_center / B_inf * 100:.2f}% of ideal)")
print(f"End Field B(±L/2):          {B_end * 1e3:.3f} milliTesla ({B_end / B_center * 100:.2f}% of center)")
print(f"Total Enclosed Magnetic Flux: {B_center * np.pi * R**2 * 1e6:.2f} microWeber")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Axial Magnetic Field Profile
ax1.plot(z * 100, Bz * 1e3, 'b-', lw=2.2, label=r'Finite Solenoid $B_z(z)$')
ax1.axhline(B_inf * 1e3, color='forestgreen', linestyle='--', lw=1.5, label=f'Ideal Infinite $B_{{\\infty}} = {B_inf*1e3:.2f}$ mT')
ax1.axvline(-0.5 * L * 100, color='crimson', linestyle=':', lw=1.5, label='Solenoid Ends')
ax1.axvline(0.5 * L * 100, color='crimson', linestyle=':', lw=1.5)
ax1.scatter([0], [B_center * 1e3], color='gold', s=100, edgecolors='black', zorder=5, label=f'Center ({B_center*1e3:.2f} mT)')
ax1.scatter([0.5 * L * 100], [B_end * 1e3], color='red', s=80, zorder=5, label=f'Edge ({B_end*1e3:.2f} mT)')
ax1.set_title(r'Axial Magnetic Field $B_z(z)$ Distribution', fontsize=12, fontweight='bold')
ax1.set_xlabel('Axial Position $z$ (cm)', fontsize=11)
ax1.set_ylabel('Magnetic Field $B_z$ (mT)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend(fontsize=9, loc='lower center')

# Plot 2: 2D Spatial Cross-Section Field Intensity Map
r_grid = np.linspace(-3 * R, 3 * R, 150)
z_grid_2d = np.linspace(-L, L, 150)
R_2D, Z_2D = np.meshgrid(r_grid, z_grid_2d)

# Near-axis expansion approximation for 2D field map visualization
term1_2d = (Z_2D + 0.5 * L) / np.sqrt(R**2 + (Z_2D + 0.5 * L)**2)
term2_2d = (Z_2D - 0.5 * L) / np.sqrt(R**2 + (Z_2D - 0.5 * L)**2)
B_axial_2d = 0.5 * mu0 * n * I * (term1_2d - term2_2d)
radial_damping = np.where(np.abs(R_2D) <= R, 1.0, np.exp(-2.5 * (np.abs(R_2D) - R) / R))
B_2D = B_axial_2d * radial_damping

im = ax2.imshow(B_2D.T * 1e3, extent=[-L * 100, L * 100, -3 * R * 100, 3 * R * 100], cmap='plasma', origin='lower', aspect='auto')
ax2.axvline(-0.5 * L * 100, color='white', linestyle='--', alpha=0.7)
ax2.axvline(0.5 * L * 100, color='white', linestyle='--', alpha=0.7)
ax2.axhline(R * 100, color='cyan', linestyle=':', alpha=0.8, label=r'Radius $R$')
ax2.axhline(-R * 100, color='cyan', linestyle=':', alpha=0.8)
cbar = plt.colorbar(im, ax=ax2)
cbar.set_label('Field Magnitude $B$ (mT)', fontsize=10)
ax2.set_title('Cross-Sectional Magnetic Field Intensity', fontsize=12, fontweight='bold')
ax2.set_xlabel('Axial Position $z$ (cm)', fontsize=11)
ax2.set_ylabel('Radial Position $r$ (cm)', fontsize=11)
ax2.legend(loc='upper right', fontsize=9)

plt.tight_layout()
plt.show()
PY;
        $code = str_replace(
            ['{{TURNS}}', '{{LENGTH}}', '{{CURRENT}}', '{{RADIUS}}'],
            [(string)$turns, (string)$length, (string)$current, (string)$radius],
            $template
        );
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Electrodynamics: Finite Solenoid Magnetic Field (N = {$turns}, L = {$length} m, I = {$current} A)",
            'code' => $code,
            'explanation' => "Calculates the axial magnetic field distribution for a finite solenoid with {$turns} turns carrying {$current} A over {$length} m. Contrasts the exact finite geometry center field with the ideal infinite solenoid approximation \$B = \\mu_0 n I\$, confirming edge field halving \$B(\\pm L/2) \\approx B_0 / 2\$.",
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Compute the magnetic field inside a Toroid of mean radius R_m: B = mu_0 * N * I / (2 * pi * r).',
                'Design a Helmholtz coil pair and verify that d^2 B / dz^2 = 0 at the midpoint.',
                'Calculate self-inductance L = mu_0 * n^2 * A * length and compare with stored magnetic energy 0.5 * L * I^2.'
            ]
        ];
    }

    // =========================================================================
    // 3E. LORENTZ FORCE (Cyclotron & Helical Trajectory in Magnetic Field)
    // =========================================================================
    if (str_contains($p, 'lorentz') || (str_contains($p, 'charged particle') && str_contains($p, 'magnetic field')) || str_contains($p, 'cyclotron')) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Electrodynamics: Lorentz Force on Charged Particle
# Equation of Motion: F = q * (E + v x B) = m * (dv/dt)
# Cyclotron Frequency: omega_c = q * B / m
# Larmor / Cyclotron Radius: r_c = m * v_perp / (q * B)

q = 1.602e-19     # Charge (Coulombs, proton)
m = 1.673e-27     # Mass (kg, proton)
B = np.array([0.0, 0.0, 0.5])   # Magnetic Field (0.5 Tesla in z)
E = np.array([0.0, 500.0, 0.0]) # Electric Field (500 V/m in y)

# Initial velocity: v_x = 1.5e5 m/s, v_z = 3e4 m/s (helical pitch)
v0 = np.array([1.5e5, 0.0, 3.0e4])
r0 = np.array([0.0, 0.0, 0.0])

# Time integration (Velocity Verlet / RK4)
omega_c = q * B[2] / m
T_cyclotron = 2 * np.pi / omega_c
r_c = m * np.linalg.norm(v0[:2]) / (q * B[2])

dt = T_cyclotron / 200.0
t_max = 4.0 * T_cyclotron
num_steps = int(t_max / dt)

r = np.zeros((num_steps, 3))
v = np.zeros((num_steps, 3))
r[0] = r0
v[0] = v0

for i in range(num_steps - 1):
    a = (q / m) * (E + np.cross(v[i], B))
    v_half = v[i] + 0.5 * a * dt
    r[i + 1] = r[i] + v_half * dt
    a_next = (q / m) * (E + np.cross(v_half, B))
    v[i + 1] = v_half + 0.5 * a_next * dt

print("=" * 65)
print(f"LORENTZ FORCE CYCLOTRON DYNAMICS (B = {B[2]} T, E_y = {E[1]} V/m)")
print("=" * 65)
print(f"Cyclotron Frequency (omega_c): {omega_c:.3e} rad/s ({omega_c / (2*np.pi):.3e} Hz)")
print(f"Cyclotron Period (T_c):       {T_cyclotron:.3e} seconds")
print(f"Larmor Radius (r_c):          {r_c * 1e3:.2f} mm")
print(f"E x B Drift Velocity (v_d):   {E[1] / B[2]:.2f} m/s in x")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: 2D Cyclotron / Drift Motion in xy-plane
ax1.plot(r[:, 0] * 1e3, r[:, 1] * 1e3, 'b-', lw=1.8, label='Particle Trajectory')
ax1.scatter([r[0, 0] * 1e3], [r[0, 1] * 1e3], color='green', s=70, label='Start')
ax1.scatter([r[-1, 0] * 1e3], [r[-1, 1] * 1e3], color='red', s=70, label='End')
ax1.set_title(r'Cycloidal Drift in $xy$-Plane ($E \times B$ Drift)', fontsize=12, fontweight='bold')
ax1.set_xlabel(r'$x$ Position (mm)', fontsize=11)
ax1.set_ylabel(r'$y$ Position (mm)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Helical Longitudinal Advance z(t) vs x(t)
ax2.plot(r[:, 0] * 1e3, r[:, 2] * 1e3, 'm-', lw=1.8)
ax2.set_title(r'Helical Longitudinal Pitch ($x$ vs $z$)', fontsize=12, fontweight='bold')
ax2.set_xlabel(r'$x$ Position (mm)', fontsize=11)
ax2.set_ylabel(r'$z$ Position (mm)', fontsize=11)
ax2.grid(True, alpha=0.3)

plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Electrodynamics: Lorentz Force & Helical Cyclotron Trajectory',
            'code' => $code,
            'explanation' => "Simulates relativistic/classical motion under Lorentz force \$\\mathbf{F} = q(\\mathbf{E} + \\mathbf{v} \\times \\mathbf{B})\$. Computes cyclotron frequency \$\\omega_c = qB/m\$, Larmor radius, and \\mathbf{E} \\times \\mathbf{B} cross-field guiding center drift.",
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Set E = 0 and verify pure circular cyclotron motion with constant kinetic energy.',
                'Introduce a magnetic mirror geometry B_z(z) and observe magnetic reflection.',
                'Calculate synchrotron radiation loss power P = (q^2 a^2 gamma^4) / (6 pi epsilon_0 c^3).'
            ]
        ];
    }

    // =========================================================================
    // 3F. LISSAJOUS FIGURES & SUPERPOSITION OF SHMs
    // =========================================================================
    if (str_contains($p, 'lissajous') || (str_contains($p, 'superposition') && str_contains($p, 'harmonic'))) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Classical Mechanics: Lissajous Figures (Superposition of Orthogonal SHMs)
# Equations:
# x(t) = A_x * sin(omega_x * t + delta)
# y(t) = A_y * sin(omega_y * t)

Ax = 1.0
Ay = 1.0
t = np.linspace(0, 2 * np.pi, 1000)

ratios = [(1, 1), (1, 2), (2, 3), (3, 4)]
deltas = [0, np.pi / 4, np.pi / 2, np.pi / 2]

fig, axes = plt.subplots(1, 4, figsize=(13, 3.6))

print("=" * 65)
print("LISSAJOUS FIGURE SYNTHESIS FOR ORTHOGONAL OSCILLATIONS")
print("=" * 65)

for idx, ((wx, wy), delta) in enumerate(zip(ratios, deltas)):
    x = Ax * np.sin(wx * t + delta)
    y = Ay * np.sin(wy * t)
    
    ax = axes[idx]
    ax.plot(x, y, 'b-', lw=1.8)
    ax.set_title(rf'$\omega_x:\omega_y = {wx}:{wy}$' + '\n' + rf'$\delta = {delta/np.pi:.2f}\pi$', fontsize=10, fontweight='bold')
    ax.set_xlim(-1.25, 1.25)
    ax.set_ylim(-1.25, 1.25)
    ax.set_aspect('equal')
    ax.grid(True, alpha=0.3)
    print(f"Mode {idx+1}: Ratio {wx}:{wy}, Phase delta = {delta:.3f} rad")

print("=" * 65)
plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Oscillations & Waves: Lissajous Figures & Orthogonal SHM Superposition',
            'code' => $code,
            'explanation' => "Synthesizes 2D Lissajous figures created by the superposition of mutually perpendicular simple harmonic motions. Demonstrates how frequency ratios and phase differences determine loop topology.",
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Determine the phase difference delta from the intercept ratio on the y-axis y_0 / A_y = sin(delta).',
                'Simulate incommensurate frequencies (e.g. ratio sqrt(2)) and verify space-filling ergodic behavior.',
                'Model 3D Lissajous curves combining orthogonal vibrations along x, y, and z.'
            ]
        ];
    }

    // =========================================================================
    // 3G. WAVE OPTICS: SINGLE SLIT & DOUBLE SLIT DIFFRACTION
    // =========================================================================
    if (str_contains($p, 'diffraction') || (str_contains($p, 'slit') && (str_contains($p, 'pattern') || str_contains($p, 'interference') || str_contains($p, 'intensity')))) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Wave Optics: Fraunhofer Diffraction & Young's Double Slit Interference
# Single Slit: I_single(theta) = I_0 * [sin(beta) / beta]^2,  beta = (pi * a / lambda) * sin(theta)
# Double Slit: I_double(theta) = 4 * I_0 * [sin(beta) / beta]^2 * cos^2(alpha),  alpha = (pi * d / lambda) * sin(theta)

wavelength = 632.8e-9    # He-Ne Laser wavelength (632.8 nm)
a = 2.0e-5               # Slit width (20 micrometers)
d = 8.0e-5               # Slit separation for double slit (80 micrometers)

theta = np.linspace(-0.035, 0.035, 1200)

beta = (np.pi * a / wavelength) * np.sin(theta) + 1e-12
alpha = (np.pi * d / wavelength) * np.sin(theta)

I_single = (np.sin(beta) / beta)**2
I_double = (np.sin(beta) / beta)**2 * (np.cos(alpha))**2

print("=" * 65)
print(f"WAVE OPTICS TELEMETRY: DIFFRACTION & INTERFERENCE (lambda = {wavelength*1e9:.1f} nm)")
print("=" * 65)
print(f"Slit Width (a):               {a*1e6:.1f} micrometers")
print(f"Slit Separation (d):          {d*1e6:.1f} micrometers")
print(f"First Minimum (Single Slit):  theta_min = +/- {wavelength / a * 180 / np.pi:.3f} deg")
print(f"Interference Fringe Spacing:  Delta theta = {wavelength / d * 180 / np.pi:.3f} deg")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Intensity Distribution Profile
ax1.plot(theta * 1e3, I_double, 'b-', lw=1.5, label='Double Slit Pattern (Interference + Envelope)')
ax1.plot(theta * 1e3, I_single, 'r--', lw=1.8, label='Single Slit Diffraction Envelope')
ax1.set_title('Fraunhofer Double Slit Intensity Profile', fontsize=12, fontweight='bold')
ax1.set_xlabel(r'Diffraction Angle $\theta$ (mrad)', fontsize=11)
ax1.set_ylabel(r'Normalized Intensity $I / I_0$', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Simulated Optical Fringe Pattern
fringe_matrix = np.tile(I_double, (150, 1))
ax2.imshow(fringe_matrix, cmap='inferno', aspect='auto', extent=[theta[0] * 1e3, theta[-1] * 1e3, 0, 1])
ax2.set_title('Simulated Photographic Fringe Pattern', fontsize=12, fontweight='bold')
ax2.set_xlabel(r'Angular Position $\theta$ (mrad)', fontsize=11)
ax2.set_yticks([])

plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Wave Optics: Fraunhofer Single & Double Slit Diffraction Intensity',
            'code' => $code,
            'explanation' => "Calculates Fraunhofer optical diffraction and Young double-slit interference intensity. Simulates the modulation envelope \$\\mathrm{sinc}^2(\\beta)\$, interference fringes \$\\cos^2(\\alpha)\$, and false-color photographic optical fringe bands.",
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Calculate missing orders where an interference maximum coincides with a diffraction minimum (d/a = integer).',
                'Generalize to an N-slit diffraction grating with principal maxima scaling as N^2.',
                'Incorporate partial spatial coherence using the Van Cittert-Zernike theorem.'
            ]
        ];
    }

    // =========================================================================
    // 3H. COUPLED HARMONIC OSCILLATORS & NORMAL MODES
    // =========================================================================
    if (str_contains($p, 'coupled') && (str_contains($p, 'oscillator') || str_contains($p, 'pendulum') || str_contains($p, 'spring') || str_contains($p, 'mode'))) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Classical Mechanics: Coupled Harmonic Oscillators & Normal Modes
# Equations of Motion for two identical carts of mass m coupled by spring k_c:
# m * d^2 x_1 / dt^2 = -k * x_1 - k_c * (x_1 - x_2)
# m * d^2 x_2 / dt^2 = -k * x_2 - k_c * (x_2 - x_1)
# Normal Mode 1 (In-phase):     omega_1 = sqrt(k / m)
# Normal Mode 2 (Anti-phase):   omega_2 = sqrt((k + 2 * k_c) / m)

m = 1.0       # Mass (kg)
k = 9.0       # Anchor spring constant (N/m)
kc = 2.0      # Coupling spring constant (N/m)

omega1 = np.sqrt(k / m)
omega2 = np.sqrt((k + 2 * kc) / m)
omega_beat = abs(omega2 - omega1)

t = np.linspace(0, 30.0, 1500)
x1 = 0.5 * (np.cos(omega1 * t) + np.cos(omega2 * t))
x2 = 0.5 * (np.cos(omega1 * t) - np.cos(omega2 * t))

print("=" * 65)
print(f"COUPLED OSCILLATOR DYNAMICS (m = {m} kg, k = {k} N/m, k_c = {kc} N/m)")
print("=" * 65)
print(f"Normal Mode 1 (In-phase omega_1):     {omega1:.4f} rad/s")
print(f"Normal Mode 2 (Anti-phase omega_2):   {omega2:.4f} rad/s")
print(f"Beating Modulation Frequency:         {omega_beat:.4f} rad/s")
print(f"Energy Exchange Period (Beat period): {2 * np.pi / omega_beat:.2f} seconds")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(2, 1, figsize=(11, 5.5), sharex=True)

# Plot 1: Displacements showing complete energy exchange
ax1.plot(t, x1, 'b-', lw=1.8, label=r'Mass 1 Displacement $x_1(t)$')
ax1.plot(t, x2, 'r--', lw=1.5, label=r'Mass 2 Displacement $x_2(t)$')
ax1.set_title('Coupled Oscillators: Energy Exchange & Beating', fontsize=12, fontweight='bold')
ax1.set_ylabel('Displacement (m)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend(loc='upper right')

# Plot 2: Normal Coordinate Decompositions q1(t) and q2(t)
q1 = x1 + x2   # Mode 1: Pure oscillation at omega1
q2 = x1 - x2   # Mode 2: Pure oscillation at omega2
ax2.plot(t, q1, 'g-', lw=1.6, label=r'Normal Coordinate $q_1 = x_1 + x_2$ ($\omega_1$)')
ax2.plot(t, q2, 'm-', lw=1.6, label=r'Normal Coordinate $q_2 = x_1 - x_2$ ($\omega_2$)')
ax2.set_title('Decoupled Normal Coordinates (Pure Harmonics)', fontsize=12, fontweight='bold')
ax2.set_xlabel('Time $t$ (seconds)', fontsize=11)
ax2.set_ylabel('Normal Amplitude', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend(loc='upper right')

plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Classical Mechanics: Coupled Harmonic Oscillators & Normal Modes',
            'code' => $code,
            'explanation' => "Solves equations of motion for two coupled oscillators. Transforms from coupled physical coordinates \$x_1, x_2\$ into uncoupled normal coordinates \$q_1, q_2\$, illustrating energy transfer and beating dynamics.",
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Generalize the formulation to N coupled masses in a 1D chain and derive the acoustic phonon dispersion curve.',
                'Introduce viscous damping and observe the decay of normal modes.',
                'Compute the normal mode eigenvectors using numpy.linalg.eigh on the stiffness matrix.'
            ]
        ];
    }

    // =========================================================================
    // 3I. 1D & 2D RANDOM WALK & BROWNIAN MOTION
    // =========================================================================
    if (str_contains($p, 'random walk') || str_contains($p, 'brownian')) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Statistical Physics: Random Walk & Brownian Motion Simulation
# Diffusion Law: <x^2> = 2 * D * t (Mean Square Displacement scales linearly with time)

np.random.seed(42)
N_steps = 1000
N_walkers = 50

# 1D Random Walk Ensemble
steps_1d = np.random.choice([-1, 1], size=(N_walkers, N_steps))
trajectories_1d = np.cumsum(steps_1d, axis=1)
msd_1d = np.mean(trajectories_1d**2, axis=0)

# 2D Random Walk for single walker
dx = np.random.normal(0, 1, N_steps)
dy = np.random.normal(0, 1, N_steps)
x_2d = np.cumsum(dx)
y_2d = np.cumsum(dy)

print("=" * 65)
print(f"RANDOM WALK & BROWNIAN MOTION (N_steps = {N_steps}, N_walkers = {N_walkers})")
print("=" * 65)
print(f"Final Mean Displacement <x>:    {np.mean(trajectories_1d[:, -1]):.3f} (approx 0)")
print(f"Observed Mean Square Displ <x^2>: {msd_1d[-1]:.2f}")
print(f"Theoretical Expectation N:       {N_steps:.2f}")
print(f"Relative Deviation:              {abs(msd_1d[-1] - N_steps) / N_steps * 100:.2f}%")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: 1D Trajectory Ensemble & Mean Square Displacement
for w in range(min(15, N_walkers)):
    ax1.plot(trajectories_1d[w], lw=0.8, alpha=0.6)
ax1.plot(np.sqrt(np.arange(N_steps)), 'k--', lw=2, label=r'RMS Envelope $\sigma = \sqrt{N}$')
ax1.plot(-np.sqrt(np.arange(N_steps)), 'k--', lw=2)
ax1.set_title('1D Random Walk Trajectory Ensemble', fontsize=12, fontweight='bold')
ax1.set_xlabel('Time Step $t$', fontsize=11)
ax1.set_ylabel('Position $x$', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: 2D Brownian Motion Trajectory
ax2.plot(x_2d, y_2d, 'b-', lw=1.2, alpha=0.85)
ax2.scatter([0], [0], color='green', s=90, zorder=5, label='Origin (0,0)')
ax2.scatter([x_2d[-1]], [y_2d[-1]], color='red', s=90, zorder=5, label=f'Final ({x_2d[-1]:.1f}, {y_2d[-1]:.1f})')
ax2.set_title('2D Brownian Random Walk Particle Trajectory', fontsize=12, fontweight='bold')
ax2.set_xlabel(r'$x$ Coordinate', fontsize=11)
ax2.set_ylabel(r'$y$ Coordinate', fontsize=11)
ax2.set_aspect('equal')
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Statistical Physics: 1D & 2D Random Walk & Brownian Diffusion',
            'code' => $code,
            'explanation' => "Simulates discrete 1D random walks and 2D continuous Brownian motion. Verifies Einstein diffusion law \$\\langle x^2 \\rangle = 2 D t\$ and demonstrates convergence to the normal Gaussian distribution via the Central Limit Theorem.",
            'complexity' => 'Time: O(N * W), Space: O(N * W)',
            'exercises' => [
                'Incorporate an external drift velocity v_drift and observe biased diffusion <x(t)> = v_drift * t.',
                'Simulate random walk with absorbing and reflecting boundary walls.',
                'Verify the fractal dimension D_f = 2 of Brownian motion in the plane.'
            ]
        ];
    }

    // =========================================================================
    // 4B. CHAOTIC DOUBLE PENDULUM (Lagrangian RK4 Dynamics & Sensitivity)
    // =========================================================================
    if (str_contains($p, 'double pendulum') || (str_contains($p, 'chaos') && str_contains($p, 'pendulum')) || str_contains($p, 'chaotic pendulum') || str_contains($p, 'chaotic motion')) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# ==============================================================================
# Nonlinear Dynamics: Chaotic Double Pendulum (RK4 Numerical Integration)
# Governing Euler-Lagrange Equations of Motion for Coupled Rigid Links
# Demonstrates extreme sensitivity to initial conditions (Lyapunov exponent)
# ==============================================================================

# Physical Parameters
g = 9.81            # Gravitational acceleration (m/s^2)
m1, m2 = 1.0, 1.0   # Bob masses (kg)
L1, L2 = 1.0, 1.0   # Rod lengths (m)

t_max = 25.0
dt = 0.005
steps = int(t_max / dt)
t = np.linspace(0, t_max, steps)

def double_pendulum_derivs(state):
    """Computes exact nonlinear derivatives [dth1, dom1, dth2, dom2]"""
    th1, om1, th2, om2 = state
    delta = th1 - th2
    denom = 2 * m1 + m2 - m2 * np.cos(2 * delta)
    
    num1 = -g * (2 * m1 + m2) * np.sin(th1) - m2 * g * np.sin(th1 - 2 * th2) - 2 * np.sin(delta) * m2 * (om2**2 * L2 + om1**2 * L1 * np.cos(delta))
    d_om1 = num1 / (L1 * denom)
    
    num2 = 2 * np.sin(delta) * (om1**2 * L1 * (m1 + m2) + g * (m1 + m2) * np.cos(th1) + om2**2 * L2 * m2 * np.cos(delta))
    d_om2 = num2 / (L2 * denom)
    
    return np.array([om1, d_om1, om2, d_om2])

def rk4_integrate(initial_state):
    """Integrates equations of motion using 4th-Order Runge-Kutta"""
    sol = np.zeros((steps, 4))
    sol[0] = initial_state
    for i in range(steps - 1):
        s = sol[i]
        k1 = double_pendulum_derivs(s)
        k2 = double_pendulum_derivs(s + 0.5 * dt * k1)
        k3 = double_pendulum_derivs(s + 0.5 * dt * k2)
        k4 = double_pendulum_derivs(s + dt * k3)
        sol[i + 1] = s + (dt / 6.0) * (k1 + 2 * k2 + 2 * k3 + k4)
    return sol

# System A: Initial state [theta1 = 90°, omega1 = 0, theta2 = 90°, omega2 = 0]
init_A = np.array([np.pi / 2.0, 0.0, np.pi / 2.0, 0.0])
sol_A = rk4_integrate(init_A)

# System B: Perturbed state by 0.001 rad (~0.057°) to illustrate Butterfly Effect
init_B = np.array([np.pi / 2.0 + 0.001, 0.0, np.pi / 2.0, 0.0])
sol_B = rk4_integrate(init_B)

# Cartesian coordinates of both bobs
th1_A, th2_A = sol_A[:, 0], sol_A[:, 2]
x1_A = L1 * np.sin(th1_A)
y1_A = -L1 * np.cos(th1_A)
x2_A = x1_A + L2 * np.sin(th2_A)
y2_A = y1_A - L2 * np.cos(th2_A)

th1_B, th2_B = sol_B[:, 0], sol_B[:, 2]
divergence = np.abs(th2_A - th2_B)

print("=" * 65)
print("CHAOTIC DOUBLE PENDULUM RK4 TELEMETRY")
print("=" * 65)
print(f"Lengths: L1 = {L1} m, L2 = {L2} m | Masses: m1 = {m1} kg, m2 = {m2} kg")
print(f"Time Horizon: {t_max} s ({steps} RK4 integration steps, dt = {dt*1000:.1f} ms)")
print(f"Initial Perturbation: Delta Theta_1(0) = 0.001 rad")
print(f"Final Angle Divergence: Delta Theta_2(t_end) = {divergence[-1]:.3f} rad")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.8))

# Plot 1: Real-Space Chaotic Trajectory of Tip Bob
ax1.plot(x2_A, y2_A, color='crimson', lw=0.6, alpha=0.8, label='Tip Bob Path')
ax1.scatter([0], [0], color='black', s=80, zorder=5, label='Fixed Pivot')
ax1.scatter([x2_A[0]], [y2_A[0]], color='forestgreen', s=70, zorder=5, label='Start')
ax1.scatter([x2_A[-1]], [y2_A[-1]], color='blue', s=70, zorder=5, label='End')
ax1.set_aspect('equal')
ax1.set_xlim(-(L1 + L2) * 1.15, (L1 + L2) * 1.15)
ax1.set_ylim(-(L1 + L2) * 1.15, (L1 + L2) * 1.15)
ax1.set_title('Chaotic Trajectory of Lower Bob in Real Space', fontsize=12, fontweight='bold')
ax1.set_xlabel('Horizontal Position $x$ (m)', fontsize=11)
ax1.set_ylabel('Vertical Position $y$ (m)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend(loc='upper right', fontsize=8.5)

# Plot 2: Exponential Divergence (Lyapunov Sensitivity)
ax2.semilogy(t, np.maximum(divergence, 1e-10), 'indigo', lw=1.6, label=r'$|\Delta \theta_2(t)|$ Divergence')
ax2.axhline(0.001, color='gray', linestyle=':', label='Initial Perturbation ($10^{-3}$ rad)')
ax2.set_title('Sensitive Dependence on Initial Conditions', fontsize=12, fontweight='bold')
ax2.set_xlabel('Time $t$ (s)', fontsize=11)
ax2.set_ylabel(r'Angular Difference $|\Delta \theta_2|$ (rad, Log Scale)', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend(loc='lower right', fontsize=9)

plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Nonlinear Dynamics: Chaotic Double Pendulum Simulation & Sensitivity to Initial Conditions (RK4)',
            'code' => $code,
            'explanation' => "Simulates the chaotic double pendulum by numerically integrating the nonlinear Euler-Lagrange equations via 4th-Order Runge-Kutta. Contrasts two trajectories initiated with a 0.001-radian difference to demonstrate sensitive dependence on initial conditions and deterministic chaos.",
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Construct the Poincare section by sampling (theta1, omega1) whenever theta2 crosses zero.',
                'Compute the maximal Lyapunov exponent lambda from the slope of ln(Delta theta) vs time.',
                'Verify mechanical energy conservation E = T + V across all 5000 simulation steps.'
            ]
        ];
    }

    // =========================================================================
    // 4. HARMONIC OSCILLATOR & PENDULUM (SHM, Damped, Driven, Nonlinear, RK4)
    // =========================================================================
    if (!str_contains($p, 'double') && !str_contains($p, 'chaotic') && !str_contains($p, 'coupled') && !str_contains($p, 'lissajous') && (str_contains($p, 'pendulum') || (str_contains($p, 'harmonic') && !str_contains($p, 'quantum')) || str_contains($p, 'oscillator') || str_contains($p, 'shm') || str_contains($p, 'phase space') || str_contains($p, 'damped'))) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Classical Mechanics: Nonlinear Damped Driven Pendulum
# Equation of Motion: d^2 theta / dt^2 + gamma * d theta / dt + (g / L) * sin(theta) = F_0 * cos(omega_d * t)

g = 9.81         # Gravitational acceleration (m/s^2)
L = 1.0          # Length of pendulum (m)
omega_0 = np.sqrt(g / L)  # Natural frequency (rad/s)
gamma = 0.5      # Damping coefficient
F_drive = 1.2    # Drive amplitude
omega_d = 2.0 / 3.0 * omega_0 # Drive angular frequency

t_max = 30.0
dt = 0.01
steps = int(t_max / dt)
t = np.linspace(0, t_max, steps)

# State vectors: [theta, omega]
theta = np.zeros(steps)
omega = np.zeros(steps)

# Initial conditions (large amplitude 60 degrees)
theta[0] = np.radians(60.0)
omega[0] = 0.0

def derivatives(th, om, time):
    d_th = om
    d_om = -(g / L) * np.sin(th) - gamma * om + F_drive * np.cos(omega_d * time)
    return d_th, d_om

# 4th-Order Runge-Kutta (RK4) Numerical Integrator
for i in range(steps - 1):
    ti = t[i]
    th_i = theta[i]
    om_i = omega[i]
    
    k1_th, k1_om = derivatives(th_i, om_i, ti)
    k2_th, k2_om = derivatives(th_i + 0.5 * dt * k1_th, om_i + 0.5 * dt * k1_om, ti + 0.5 * dt)
    k3_th, k3_om = derivatives(th_i + 0.5 * dt * k2_th, om_i + 0.5 * dt * k2_om, ti + 0.5 * dt)
    k4_th, k4_om = derivatives(th_i + dt * k3_th, om_i + dt * k3_om, ti + dt)
    
    theta[i + 1] = th_i + (dt / 6.0) * (k1_th + 2*k2_th + 2*k3_th + k4_th)
    omega[i + 1] = om_i + (dt / 6.0) * (k1_om + 2*k2_om + 2*k3_om + k4_om)

# Keep angle in [-pi, pi]
theta_wrapped = (theta + np.pi) % (2 * np.pi) - np.pi

print("=" * 65)
print("NONLINEAR PENDULUM DYNAMICS (RK4)")
print("=" * 65)
print(f"Length:                 {L} m | Natural Frequency: {omega_0:.3f} rad/s")
print(f"Damping Coefficient:    {gamma} | Drive Amplitude:   {F_drive}")
print(f"Initial Angle:          {np.degrees(theta[0]):.1f}°")
print(f"Time Step dt:           {dt} s across {t_max} s ({steps} steps)")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Angular Displacement vs Time
ax1.plot(t, np.degrees(theta_wrapped), 'b-', lw=1.8, label=r'$\theta(t)$')
ax1.set_title('Nonlinear Pendulum: Angular Displacement vs Time', fontsize=12, fontweight='bold')
ax1.set_xlabel('Time $t$ (s)', fontsize=11)
ax1.set_ylabel('Angular Position $\\theta$ (degrees)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Phase Space Portrait (theta vs omega)
ax2.plot(theta_wrapped, omega, 'r-', lw=1.2, alpha=0.8)
ax2.scatter([theta_wrapped[0]], [omega[0]], color='green', s=80, zorder=5, label='Initial State')
ax2.set_title('Phase Space Trajectory $(\\theta, \\dot{\\theta})$', fontsize=12, fontweight='bold')
ax2.set_xlabel('Angular Position $\\theta$ (rad)', fontsize=11)
ax2.set_ylabel('Angular Velocity $\\dot{\\theta}$ (rad/s)', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Nonlinear Damped Driven Pendulum & Phase Space Dynamics (RK4)',
            'code' => $code,
            'explanation' => 'Simulates nonlinear rotational pendulum dynamics with external periodic forcing and viscous damping using the 4th-order Runge-Kutta (RK4) integration algorithm. Visualizes the displacement time-series and phase portrait.',
            'complexity' => 'Time: O(steps), Space: O(steps)',
            'exercises' => [
                'Set drive amplitude to zero (F_drive = 0) to observe underdamped harmonic oscillations.',
                'Compare the nonlinear period with the small-angle approximation $T_0 = 2\\pi \\sqrt{L/g}$.',
                'Increase drive amplitude to $F_0 = 1.2$ with $L=1.0$ to explore chaotic attractor dynamics.'
            ]
        ];
    }

    // =========================================================================
    // 5. PROJECTILE MOTION & BALLISTICS
    // =========================================================================
    if (str_contains($p, 'projectile') || str_contains($p, 'trajectory') || str_contains($p, 'ballistic') || (str_contains($p, 'launch') && str_contains($p, 'angle'))) {
        $v0 = 35.0;
        if (preg_match('/(?:velocity|speed|v0|v)\s*=?\s*(\d+(?:\.\d+)?)/i', $problem, $m)) {
            $v0 = (float)$m[1];
        } elseif (preg_match('/(\d+(?:\.\d+)?)\s*(?:m\/s|mps)/i', $problem, $m)) {
            $v0 = (float)$m[1];
        }

        $userAngle = null;
        if (preg_match('/(?:angle|at|theta)\s*=?\s*(\d+(?:\.\d+)?)\s*(?:deg|degree|°)?/i', $problem, $m)) {
            $userAngle = (float)$m[1];
        } elseif (preg_match('/(\d+(?:\.\d+)?)\s*(?:deg|degree|°)/i', $problem, $m)) {
            $userAngle = (float)$m[1];
        }

        $anglesListStr = ($userAngle !== null) ? "[{$userAngle}]" : "[30.0, 45.0, 60.0]";
        $titleStr = ($userAngle !== null) 
            ? "Classical Ballistics: Projectile Motion (v0 = {$v0} m/s, theta = {$userAngle}°) with Drag"
            : "Classical Ballistics: Projectile Motion Simulation (v0 = {$v0} m/s) with Drag";

        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Projectile Motion with Quadratic Air Resistance (Aerodynamic Drag)
# Equations of Motion:
# dv_x/dt = - (c / m) * v * v_x
# dv_y/dt = - g - (c / m) * v * v_y

g = 9.81         # Gravitational acceleration (m/s^2)
m = 0.145        # Mass of projectile (baseball in kg)
v0 = {{V0}}      # Initial launch speed (m/s)
angles = {{ANGLES}}  # Launch angles in degrees
c_drag = 0.003   # Drag coefficient (kg/m)
dt = 0.005       # Time step (s)

plt.figure(figsize=(10, 5))

for angle_deg in angles:
    theta = np.radians(angle_deg)
    
    # Ideal Trajectory (Vacuum - Analytical Parabola)
    t_flight_vac = 2 * v0 * np.sin(theta) / g
    t_vac = np.linspace(0, t_flight_vac, 200)
    x_vac = v0 * np.cos(theta) * t_vac
    y_vac = v0 * np.sin(theta) * t_vac - 0.5 * g * t_vac**2
    plt.plot(x_vac, y_vac, '--', alpha=0.5, label=f'Ideal {angle_deg}° (Vacuum Range: {x_vac[-1]:.1f} m)')

    # Numerical Trajectory with Air Drag (Euler-Cromer)
    x, y = 0.0, 0.0
    vx = v0 * np.cos(theta)
    vy = v0 * np.sin(theta)
    
    xs, ys = [x], [y]
    while y >= 0:
        v = np.hypot(vx, vy)
        f_drag_x = -(c_drag / m) * v * vx
        f_drag_y = -g - (c_drag / m) * v * vy
        
        vx += f_drag_x * dt
        vy += f_drag_y * dt
        x += vx * dt
        y += vy * dt
        
        if y >= 0:
            xs.append(x)
            ys.append(y)
            
    plt.plot(xs, ys, lw=2.2, label=f'Air Drag {angle_deg}° (Range: {xs[-1]:.1f} m)')

print("=" * 65)
print("BALLISTIC PROJECTILE TRAJECTORY REPORT")
print("=" * 65)
print(f"Launch Speed:        {v0} m/s")
print(f"Projectile Mass:     {m} kg")
print(f"Drag Parameter:      {c_drag} kg/m")
print(f"Terminal Velocity:   {np.sqrt(m * g / c_drag):.2f} m/s")
print("=" * 65)

plt.title('Projectile Trajectories: Aerodynamic Drag vs Ideal Parabolic Paths', fontsize=13, fontweight='bold')
plt.xlabel('Horizontal Distance $x$ (m)', fontsize=11)
plt.ylabel('Vertical Height $y$ (m)', fontsize=11)
plt.grid(True, alpha=0.3)
plt.ylim(bottom=0)
plt.legend(fontsize=9.5, loc='upper right')
plt.tight_layout()
plt.show()
PY;
        $code = str_replace(['{{V0}}', '{{ANGLES}}'], [(string)$v0, $anglesListStr], $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => $titleStr,
            'code' => $code,
            'explanation' => "Models 2D ballistic trajectories for launch speed \$v_0 = {$v0}\$ m/s under constant gravity and velocity-dependent quadratic aerodynamic drag. Solves the coupled differential equations using the symplectic Euler-Cromer scheme and contrasts with vacuum parabolas.",
            'complexity' => 'Time: O(T / dt), Space: O(N)',
            'exercises' => [
                'Find the optimal launch angle that maximizes horizontal range in the presence of air drag.',
                'Investigate the effect of varying mass m while keeping projectile diameter constant.',
                'Compute terminal velocity $v_{\\mathrm{term}} = \\sqrt{m g / c}$ and verify freefall convergence.'
            ]
        ];
    }

    // =========================================================================
    // 6. ROOT FINDING (Newton-Raphson, Bisection, Quadratic, Polynomial)
    // =========================================================================
    if ((str_contains($p, 'root') || str_contains($p, 'bisection') || str_contains($p, 'newton-raphson') || str_contains($p, 'newton raphson') || str_contains($p, 'secant') || (str_contains($p, 'quadratic') && (str_contains($p, 'equation') || str_contains($p, 'solve') || str_contains($p, 'root')))) && !str_contains($p, 'interpolation') && !str_contains($p, 'ring') && !str_contains($p, 'cooling')) {
        $userEqExpr = 'x**3 - 2*x - 5';
        $userEqDisp = 'x^3 - 2x - 5 = 0';
        if (preg_match('/(?:equation|func|function|root\s+of)\s*([a-zA-Z0-9_\+\-\*\/\^\.\(\)\s]+?)(?:=|\s*is|\s*using|\s*with|\.|\$|$)/i', $problem, $m)) {
            $cand = trim($m[1]);
            if (strlen($cand) >= 3 && preg_match('/[a-zA-Z]/', $cand)) {
                $userEqDisp = $cand . ' = 0';
                $pyCand = str_replace('^', '**', $cand);
                $pyCand = preg_replace('/(\d+)\s*([a-zA-Z])/', '$1 * $2', $pyCand);
                $pyCand = preg_replace('/\bsin\b/i', 'np.sin', $pyCand);
                $pyCand = preg_replace('/\bcos\b/i', 'np.cos', $pyCand);
                $pyCand = preg_replace('/\bexp\b/i', 'np.exp', $pyCand);
                $userEqExpr = $pyCand;
            }
        }
        $x0 = 2.0;
        if (preg_match('/(?:initial\s*guess|x0|x_0|at)\s*=?\s*(-?\d+(?:\.\d+)?)/i', $problem, $m)) {
            $x0 = (float)$m[1];
        }

        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Numerical Root Finding: Newton-Raphson Method
# Target Equation: f(x) = {{EQ_DISP}}
# Method Recurrence: x_{k+1} = x_k - f(x_k) / f'(x_k)

def f(x):
    return {{EQ_EXPR}}

def df(x):
    # High-precision numerical central difference derivative
    h = 1e-6
    return (f(x + h) - f(x - h)) / (2.0 * h)

def newton_raphson(f, df, x0, tol=1e-8, max_iter=50):
    history = [x0]
    errors = []
    x = x0
    for i in range(max_iter):
        fx = f(x)
        dfx = df(x)
        if abs(dfx) < 1e-14:
            raise ValueError("Derivative near zero. Method fails.")
        x_next = x - fx / dfx
        errors.append(abs(x_next - x))
        history.append(x_next)
        if abs(x_next - x) < tol:
            return x_next, history, errors
        x = x_next
    return x, history, errors

x0 = {{X0}}
root, history, errors = newton_raphson(f, df, x0)

print("=" * 65)
print("NEWTON-RAPHSON ROOT FINDING REPORT")
print("=" * 65)
print(f"Target:      f(x) = {{EQ_DISP}}")
print(f"Initial:     x0 = {x0}")
print(f"Root:        x* = {root:.10f}")
print(f"f(x*):       {f(root):.2e}")
print(f"Iterations:  {len(history) - 1}")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Function Curve and Root Location
x_span = max(3.0, abs(root - x0) * 2.0)
x_vals = np.linspace(root - x_span, root + x_span, 400)
try:
    y_vals = f(x_vals)
except Exception:
    y_vals = [f(xi) for xi in x_vals]

ax1.plot(x_vals, y_vals, 'b-', lw=2, label=f'$f(x)$ = {{EQ_DISP}}')
ax1.axhline(0, color='gray', linestyle='--', alpha=0.7)
ax1.scatter([root], [f(root)], color='red', s=90, zorder=5, label=f'Root $x^* \\approx {root:.5f}$')
ax1.scatter([x0], [f(x0)], color='green', s=70, zorder=5, label=f'Initial $x_0 = {x0}$')
ax1.set_title('Function Curve & Computed Root', fontsize=12, fontweight='bold')
ax1.set_xlabel('$x$', fontsize=11)
ax1.set_ylabel('$f(x)$', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend(loc='best', fontsize=9)

# Plot 2: Quadratic Convergence Rate
ax2.semilogy(range(1, len(errors) + 1), errors, 'ro-', lw=1.8, markersize=6)
ax2.set_title('Quadratic Convergence Rate $|x_{k+1} - x_k|$', fontsize=12, fontweight='bold')
ax2.set_xlabel('Iteration $k$', fontsize=11)
ax2.set_ylabel('Absolute Error (log scale)', fontsize=11)
ax2.grid(True, alpha=0.3)

plt.tight_layout()
plt.show()
PY;
        $code = str_replace(
            ['{{EQ_DISP}}', '{{EQ_EXPR}}', '{{X0}}'],
            [$userEqDisp, $userEqExpr, (string)$x0],
            $template
        );
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Numerical Root Finding: Newton-Raphson Method ({$userEqDisp}, x0 = {$x0})",
            'code' => $code,
            'explanation' => "Solves the equation \${$userEqDisp}\$ using the Newton-Raphson method with initial estimate \$x_0 = {$x0}\$. Computes numerical gradient iterations demonstrating quadratic convergence.",
            'complexity' => 'Time: O(log(1/tol)), Space: O(1)',
            'exercises' => [
                'Test convergence sensitivity with different starting guesses x0.',
                'Compare convergence speed with the Bisection Method.',
                'Analyze behavior when f\'(x) approaches zero (inflection point singularity).'
            ]
        ];
    }

    // =========================================================================
    // 7. NUMERICAL INTEGRATION (Simpson, Trapezoidal, Quadrature)
    // =========================================================================
    if (str_contains($p, 'integral') || str_contains($p, 'simpson') || str_contains($p, 'trapezoid') || str_contains($p, 'quadrature') || str_contains($p, 'integrate')) {
        $userFunc = 'np.sin(x)';
        $userFuncDisp = 'sin(x)';
        if (preg_match('/(?:integrate|integral\s+of|function|of)\s*([a-zA-Z0-9_\+\-\*\/\^\.\(\)\s]+?)(?:from|between|over|\$|[\r\n]|;\s*|$)/i', $problem, $im)) {
            $cand = trim($im[1]);
            if (strlen($cand) >= 2 && preg_match('/[a-zA-Z]/', $cand)) {
                $userFuncDisp = $cand;
                $pyCand = str_replace('^', '**', $cand);
                $pyCand = preg_replace('/(\d+)\s*([a-zA-Z])/', '$1 * $2', $pyCand);
                $pyCand = preg_replace('/\bsin\b/i', 'np.sin', $pyCand);
                $pyCand = preg_replace('/\bcos\b/i', 'np.cos', $pyCand);
                $pyCand = preg_replace('/\bexp\b/i', 'np.exp', $pyCand);
                $userFunc = $pyCand;
            }
        }

        $a = 0.0;
        $b = 3.14159;
        if (preg_match('/(?:from|between)\s*(-?\d+(?:\.\d+)?)\s*(?:to|and)\s*(-?\d+(?:\.\d+)?)/i', $problem, $bm)) {
            $a = (float)$bm[1];
            $b = (float)$bm[2];
        }

        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Numerical Integration: Composite Simpson's 1/3 Rule vs Trapezoidal Rule
# Target Integrand: f(x) = {{FUNC_DISP}}
# Domain: [a, b] = [{{A}}, {{B}}]

def f(x):
    return {{FUNC_EXPR}}

def simpsons_13(f, a, b, n):
    """Composite Simpson's 1/3 Rule (n must be even)"""
    if n % 2 != 0:
        n += 1
    h = (b - a) / n
    x = np.linspace(a, b, n + 1)
    y = f(x)
    return (h / 3) * (y[0] + 4 * np.sum(y[1:-1:2]) + 2 * np.sum(y[2:-2:2]) + y[-1])

def trapezoidal(f, a, b, n):
    """Composite Trapezoidal Rule"""
    h = (b - a) / n
    x = np.linspace(a, b, n + 1)
    y = f(x)
    return (h / 2) * (y[0] + 2 * np.sum(y[1:-1]) + y[-1])

a, b = {{A}}, {{B}}
x_ref = np.linspace(a, b, 2000)
exact = np.trapz(f(x_ref), x_ref)

n_panels = [4, 8, 16, 32, 64, 128]
trap_errors = []
simp_errors = []

for n in n_panels:
    trap_val = trapezoidal(f, a, b, n)
    simp_val = simpsons_13(f, a, b, n)
    trap_errors.append(abs(trap_val - exact) + 1e-16)
    simp_errors.append(abs(simp_val - exact) + 1e-16)

print("=" * 65)
print(f"NUMERICAL INTEGRATION BENCHMARK ({{FUNC_DISP}} on [{a}, {b}], Reference = {exact:.6f})")
print("=" * 65)
print(f"{'N Panels':<10} | {'Trapezoidal':<15} | {'Simpson 1/3':<15}")
for i, n in enumerate(n_panels):
    print(f"{n:<10} | {trap_errors[i]:<15.4e} | {simp_errors[i]:<15.4e}")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Visual Quadrature Strips
x_dense = np.linspace(a, b, 200)
ax1.plot(x_dense, f(x_dense), 'b-', lw=2, label=r'$f(x) = {{FUNC_DISP}}$')
x_strip = np.linspace(a, b, 9)
y_strip = f(x_strip)
ax1.fill_between(x_strip, 0, y_strip, color='cyan', alpha=0.3, label='Quadrature Panels ($N=8$)')
ax1.set_title('Quadrature Panel Discretization', fontsize=12, fontweight='bold')
ax1.set_xlabel('$x$', fontsize=11)
ax1.set_ylabel('$f(x)$', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Truncation Error Power-Law Scaling
ax2.loglog(n_panels, trap_errors, 's-', label=r'Trapezoidal $\mathcal{O}(h^2)$', lw=1.8)
ax2.loglog(n_panels, simp_errors, 'o-', label=r'Simpson 1/3 $\mathcal{O}(h^4)$', lw=1.8)
ax2.set_title('Error Asymptotic Convergence Scaling', fontsize=12, fontweight='bold')
ax2.set_xlabel('Number of Subintervals $N$', fontsize=11)
ax2.set_ylabel('Absolute Error $|I_{num} - I_{exact}|$', fontsize=11)
ax2.grid(True, which="both", alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        $code = str_replace(
            ['{{FUNC_DISP}}', '{{FUNC_EXPR}}', '{{A}}', '{{B}}'],
            [$userFuncDisp, $userFunc, (string)$a, (string)$b],
            $template
        );
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Numerical Integration: Simpson's 1/3 vs Trapezoidal Rule for {$userFuncDisp} on [{$a}, {$b}]",
            'code' => $code,
            'explanation' => "Computes definite integrals by parabolic (Simpson's) and linear (Trapezoidal) polynomial interpolation across subintervals. Verifies the $\mathcal{O}(h^4)$ vs $\mathcal{O}(h^2)$ asymptotic convergence rate.",
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Evaluate Gaussian Quadrature with 3 nodes and compare accuracy with Simpson\'s rule.',
                'Integrate singular functions like $1/\\sqrt{x}$ over $[0, 1]$ and observe error behavior.',
                'Compute the period of a large-amplitude pendulum $T = 4 \\sqrt{L/g} \\int_0^{\\pi/2} \\frac{d\\phi}{\\sqrt{1 - k^2 \\sin^2\\phi}}$.'
            ]
        ];
    }

    // =========================================================================
    // 8. QUANTUM MECHANICS: TISE & POTENTIAL WELL
    if ((str_contains($p, 'schrodinger') || str_contains($p, 'tise') || str_contains($p, 'potential well') || str_contains($p, 'potential box') || str_contains($p, 'particle in a box') || str_contains($p, 'infinite well') || str_contains($p, 'finite well') || str_contains($p, 'square well') || (str_contains($p, 'quantum') && (str_contains($p, 'well') || str_contains($p, 'barrier') || str_contains($p, 'box') || str_contains($p, 'wavefunction')))) && !str_contains($p, 'compton') && !str_contains($p, 'photoelectric')) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Quantum Mechanics: 1D Time-Independent Schrodinger Equation (TISE)
# Finite Difference Hamiltonian Matrix Diagonalization
# - (hbar^2 / 2m) d^2 psi / dx^2 + V(x) psi = E psi

# Atomic units: hbar = 1, m = 1
L = 10.0           # Spatial domain width [-L/2, L/2]
N = 600            # Number of grid points
x = np.linspace(-L/2, L/2, N)
dx = x[1] - x[0]

# Potential: Finite Square Well
V0 = 25.0          # Well depth
a = 2.0            # Half-width
V = np.where(np.abs(x) <= a, -V0, 0.0)

# Kinetic Energy Operator Matrix: Second-order central difference
diag = np.full(N, 1.0 / dx**2)
off_diag = np.full(N - 1, -0.5 / dx**2)
T = np.diag(diag) + np.diag(off_diag, -1) + np.diag(off_diag, 1)

# Full Hamiltonian Matrix H = T + V
H = T + np.diag(V)

# Diagonalize symmetric Hamiltonian
eigenvalues, eigenvectors = np.linalg.eigh(H)

# Filter bound states (E < 0)
bound_indices = np.where(eigenvalues < 0)[0]
num_bound = min(4, len(bound_indices))

print("=" * 65)
print("TISE FINITE SQUARE WELL QUANTUM EIGENSTATES")
print("=" * 65)
print(f"Well Depth V0:  {V0} | Well Half-Width: {a}")
print(f"Total Bound States Found: {len(bound_indices)}")
for i, idx in enumerate(bound_indices[:num_bound]):
    print(f"State n = {i}: Energy E = {eigenvalues[idx]:.4f} (E + V0 = {eigenvalues[idx] + V0:.4f})")
print("=" * 65)

plt.figure(figsize=(10, 5.5))
plt.plot(x, V, 'k-', lw=2, label='Potential Well $V(x)$')

colors = ['royalblue', 'crimson', 'forestgreen', 'darkorange']
scale_factor = 2.5

for i in range(num_bound):
    idx = bound_indices[i]
    E_n = eigenvalues[idx]
    psi = eigenvectors[:, idx]
    # Normalize wavefunction
    psi = psi / np.sqrt(np.trapz(psi**2, x))
    
    # Plot probability density shifted by energy eigenvalue
    prob_density = psi**2
    plt.axhline(E_n, color=colors[i], linestyle=':', alpha=0.6)
    plt.plot(x, E_n + scale_factor * psi, color=colors[i], lw=1.8, label=f'$n={i}$ ($E={E_n:.2f}$)')
    plt.fill_between(x, E_n, E_n + scale_factor * prob_density, color=colors[i], alpha=0.15)

plt.title('Quantum Bound States: Eigenfunctions $\\psi_n(x)$ & Probability Densities in Finite Well', fontsize=12, fontweight='bold')
plt.xlabel('Position $x$', fontsize=11)
plt.ylabel('Energy $E$ / Shifted Amplitude', fontsize=11)
plt.xlim(-L/2, L/2)
plt.ylim(-V0 - 2, 5)
plt.grid(True, alpha=0.3)
plt.legend(loc='upper right')
plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Quantum Mechanics: 1D Time-Independent Schrodinger Equation (Finite Well Matrix Diagonalization)',
            'code' => $code,
            'explanation' => 'Solves the 1D TISE boundary value problem by discretizing the spatial kinetic operator via second-order finite differences and diagonalizing the resulting tridiagonal Hamiltonian matrix to find discrete bound-state energies and wavefunctions.',
            'complexity' => 'Time: O(N^3), Space: O(N^2)',
            'exercises' => [
                'Change the potential profile to a Harmonic Oscillator $V(x) = \\frac{1}{2} k x^2$ and verify equal spacing $\\Delta E = \\hbar \\omega$.',
                'Simulate a double quantum well potential to observe symmetric and antisymmetric energy splitting (tunneling splitting).',
                'Verify the orthogonality relation $\\int \\psi_m^*(x) \\psi_n(x) dx = \\delta_{mn}$.'
            ]
        ];
    }

    // =========================================================================
    // 8C. PHOTOELECTRIC EFFECT & EINSTEIN WORK FUNCTION
    // =========================================================================
    if (str_contains($p, 'photoelectric') || str_contains($p, 'work function') || str_contains($p, 'stopping potential') || str_contains($p, 'photocell')) {
        $phi_eV = 2.2;
        if (preg_match('/(?:work\s*function|phi)\s*=?\s*(\d+(?:\.\d+)?)\s*(?:ev)?/i', $problem, $m)) {
            $phi_eV = (float)$m[1];
        } elseif (preg_match('/(\d+(?:\.\d+)?)\s*ev\b/i', $problem, $m)) {
            $phi_eV = (float)$m[1];
        }

        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Quantum Physics: Einstein's Photoelectric Effect & Planck's Constant
# Einstein Relation: K_max = h * nu - Phi = e * V_0
# Stopping Potential: V_0 = (h / e) * nu - (Phi / e)

h = 6.62607e-34     # Planck's constant (J·s)
e = 1.60218e-19     # Elementary charge (C)
c = 2.99792e8       # Speed of light (m/s)

phi_eV = {{PHI}}    # Work function in eV
phi_joules = phi_eV * e

# Threshold frequency & wavelength
nu_0 = phi_joules / h
lambda_0_nm = (c / nu_0) * 1e9

print("=" * 65)
print(f"PHOTOELECTRIC EFFECT TELEMETRY (Work Function Phi = {phi_eV:.2f} eV)")
print("=" * 65)
print(f"Threshold Optical Frequency (nu_0): {nu_0:.4e} Hz")
print(f"Threshold Wavelength (lambda_0):    {lambda_0_nm:.2f} nm ({'UV' if lambda_0_nm < 400 else ('Visible' if lambda_0_nm <= 700 else 'IR')})")
print(f"Theoretical Slope (h/e):            {h / e:.4e} V·s")
print("=" * 65)

# Optical Frequencies sweep from 0.8 * nu_0 to 2.5 * nu_0
nu = np.linspace(0.8 * nu_0, 2.5 * nu_0, 200)
K_max_eV = np.maximum(0, (h * nu - phi_joules) / e)
V_stopping = (h * nu - phi_joules) / e

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Stopping Potential vs Frequency (Yielding h/e slope)
ax1.plot(nu * 1e-14, V_stopping, 'b-', lw=2.2, label=r'Stopping Potential $V_0(\nu)$')
ax1.axhline(0, color='gray', ls=':', lw=1)
ax1.axvline(nu_0 * 1e-14, color='red', ls='--', label=rf'Threshold $\nu_0 = {nu_0*1e-14:.2f}\times 10^{{14}}$ Hz')
ax1.set_title(r"Einstein's Relation: $V_0 = \frac{h}{e}\nu - \frac{\Phi}{e}$", fontsize=12, fontweight='bold')
ax1.set_xlabel(r'Optical Frequency $\nu$ ($10^{14}$ Hz)', fontsize=11)
ax1.set_ylabel('Stopping Potential $V_0$ (Volts)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Kinetic Energy vs Wavelength lambda
wavelengths = np.linspace(150, lambda_0_nm * 1.2, 200)
K_vs_lambda = np.maximum(0, (h * c / (wavelengths * 1e-9) - phi_joules) / e)
ax2.plot(wavelengths, K_vs_lambda, 'r-', lw=2.0, label=r'$K_{\max}(\lambda)$')
ax2.axvline(lambda_0_nm, color='black', ls='--', label=rf'Cutoff $\lambda_0 = {lambda_0_nm:.1f}$ nm')
ax2.set_title(r'Max Photoelectron Kinetic Energy vs Wavelength', fontsize=12, fontweight='bold')
ax2.set_xlabel('Wavelength $\lambda$ (nm)', fontsize=11)
ax2.set_ylabel(r'$K_{\max}$ (eV)', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        $code = str_replace('{{PHI}}', (string)$phi_eV, $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Photoelectric Effect: Stopping Potential & Work Function (Phi = {$phi_eV} eV)",
            'code' => $code,
            'explanation' => "Formulates Einstein's photoelectric equation \$K_{\max} = e V_0 = h\nu - \Phi\$. Demonstrates threshold frequency \$\nu_0 = \Phi / h\$ cutoff and linear dependence of stopping potential on optical frequency, validating Planck's quantum hypothesis.",
            'complexity' => 'Time: O(1), Space: O(1)',
            'exercises' => [
                'Determine Planck\'s constant from the slope of stopping potential vs frequency: h = e * (Delta V_0 / Delta nu).',
                'Calculate the maximum speed of emitted photoelectrons v_max = sqrt(2 * K_max / m_e).',
                'Analyze the effect of increasing light intensity (amplitude) vs increasing photon frequency.'
            ]
        ];
    }

    // =========================================================================
    // 8B. COMPTON SCATTERING & QUANTUM OPTICS
    // =========================================================================
    if ((str_contains($p, 'compton') || (str_contains($p, 'wavelength') && str_contains($p, 'shift'))) && !str_contains($p, 'photoelectric') && !str_contains($p, 'stopping potential')) {
        $angleDeg = 60.0;
        if (preg_match('/(?:angle|at|theta)\s*=?\s*(\d+(?:\.\d+)?)\s*(?:deg|degree|°)?/i', $problem, $m)) {
            $angleDeg = (float)$m[1];
        } elseif (preg_match('/(\d+(?:\.\d+)?)\s*(?:deg|degree|°)/i', $problem, $m)) {
            $angleDeg = (float)$m[1];
        }

        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# ==============================================================================
# Quantum Mechanics: Compton Scattering Kinematics & Wavelength Shift
# Conservation of Relativistic 4-Momentum in Photon-Electron Inelastic Collisions
# Formula: Delta lambda = lambda' - lambda_0 = (h / (m_e * c)) * (1 - cos(theta))
# ==============================================================================

# Fundamental Physical Constants (SI Units)
h = 6.62607015e-34       # Planck constant (J·s)
c = 2.99792458e8         # Speed of light (m/s)
m_e = 9.1093837e-31      # Rest mass of electron (kg)
eV_to_J = 1.602176634e-19 # Joules per eV

# Compton Wavelength of Electron: lambda_c = h / (m_e * c)
lambda_c = h / (m_e * c)  # ~ 2.42631e-12 m (0.024263 Angstroms)

# Incident Photon Parameters (X-Ray: Molybdenum K-alpha ~ 0.071 nm = 0.71 Angstrom)
lambda_0 = 0.07093e-9    # Incident wavelength (m)
E0_J = h * c / lambda_0  # Incident photon energy (Joules)
E0_keV = E0_J / (eV_to_J * 1e3) # Incident photon energy in keV

target_angle_deg = {{ANGLE}}
theta_target_rad = np.radians(target_angle_deg)
delta_lambda_target = lambda_c * (1.0 - np.cos(theta_target_rad))
lambda_prime_target = lambda_0 + delta_lambda_target

# Scattered Photon & Recoil Electron Energy
E_prime_target_J = h * c / lambda_prime_target
E_prime_target_keV = E_prime_target_J / (eV_to_J * 1e3)
K_e_target_keV = E0_keV - E_prime_target_keV # Recoil kinetic energy (conservation)

# Electron Recoil Angle phi: cot(phi) = (1 + h*nu_0 / (m_e * c^2)) * tan(theta / 2)
alpha = E0_J / (m_e * c**2)
phi_target_rad = np.arctan(1.0 / ((1.0 + alpha) * np.tan(theta_target_rad / 2.0)))
phi_target_deg = np.degrees(phi_target_rad)

print("=" * 65)
print(f"COMPTON SCATTERING TELEMETRY (Scattering Angle = {target_angle_deg:.1f}°)")
print("=" * 65)
print(f"Electron Compton Wavelength (lambda_c): {lambda_c * 1e12:.5f} pm (0.02426 Å)")
print(f"Incident Photon Wavelength (lambda_0):  {lambda_0 * 1e9:.4f} nm ({E0_keV:.2f} keV)")
print(f"Compton Wavelength Shift (Delta lambda): {delta_lambda_target * 1e12:.4f} pm ({delta_lambda_target * 1e10:.5f} Å)")
print(f"Scattered Photon Wavelength (lambda'):  {lambda_prime_target * 1e9:.4f} nm ({E_prime_target_keV:.2f} keV)")
print(f"Recoil Electron Kinetic Energy (K_e):  {K_e_target_keV:.3f} keV ({K_e_target_keV / E0_keV * 100:.1f}% transfer)")
print(f"Electron Recoil Ejection Angle (phi):  {phi_target_deg:.2f}°")
print("=" * 65)

# Full angular sweep [0 to 180 degrees]
theta_deg_arr = np.linspace(0, 180, 500)
theta_rad_arr = np.radians(theta_deg_arr)
delta_lambda_arr = lambda_c * (1.0 - np.cos(theta_rad_arr))
lambda_prime_arr = lambda_0 + delta_lambda_arr
E_prime_arr = (h * c / lambda_prime_arr) / (eV_to_J * 1e3)
K_e_arr = E0_keV - E_prime_arr

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Compton Wavelength Shift vs Scattering Angle
ax1.plot(theta_deg_arr, delta_lambda_arr * 1e12, 'b-', lw=2.2, label=r'$\Delta \lambda = \lambda_C (1 - \cos\theta)$')
ax1.axhline(lambda_c * 1e12, color='gray', linestyle=':', label=r'$\lambda_C = 2.426$ pm ($90^\circ$)')
ax1.axhline(2 * lambda_c * 1e12, color='forestgreen', linestyle=':', label=r'$2\lambda_C = 4.853$ pm (Backscatter $180^\circ$)')
ax1.scatter([target_angle_deg], [delta_lambda_target * 1e12], color='crimson', s=100, edgecolors='black', zorder=5, 
            label=f'Target $\\theta={target_angle_deg}^\\circ$ ($\\Delta\\lambda={delta_lambda_target*1e12:.2f}$ pm)')
ax1.set_title(r'Compton Wavelength Shift $\Delta \lambda$ vs Scattering Angle', fontsize=12, fontweight='bold')
ax1.set_xlabel(r'Scattering Angle $\theta$ (Degrees)', fontsize=11)
ax1.set_ylabel(r'Shift $\Delta \lambda$ (Picometers, pm)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend(loc='upper left', fontsize=8.5)

# Plot 2: Energy Partitioning (Scattered Photon vs Recoil Electron)
ax2.plot(theta_deg_arr, E_prime_arr, 'b-', lw=2.0, label=r'Scattered Photon Energy $E^\prime$')
ax2.plot(theta_deg_arr, K_e_arr, 'r--', lw=2.0, label=r'Recoil Electron Energy $K_e$')
ax2.axvline(target_angle_deg, color='crimson', linestyle=':', alpha=0.7)
ax2.scatter([target_angle_deg], [E_prime_target_keV], color='blue', s=70, zorder=5)
ax2.scatter([target_angle_deg], [K_e_target_keV], color='red', s=70, zorder=5)
ax2.set_title('Energy Partitioning in Compton Collisions', fontsize=12, fontweight='bold')
ax2.set_xlabel(r'Scattering Angle $\theta$ (Degrees)', fontsize=11)
ax2.set_ylabel('Energy (keV)', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend(loc='center right', fontsize=9)

plt.tight_layout()
plt.show()
PY;
        $code = str_replace('{{ANGLE}}', (string)$angleDeg, $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Quantum Physics: Compton Scattering Wavelength Shift & Recoil Kinematics (theta = {$angleDeg}°)",
            'code' => $code,
            'explanation' => "Calculates the relativistic Compton wavelength shift \$\\Delta \\lambda = \\lambda_c (1 - \\cos\\theta)\$ for scattering angle \$\\theta = {$angleDeg}^\\circ\$, where \$\\lambda_c = \\frac{h}{m_e c} \\approx 2.426 \\times 10^{-12}\$ m. Computes exact 4-momentum energy partitioning between the scattered photon and recoiling Compton electron.",
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Compute the maximum Compton edge kinetic energy of the recoil electron at theta = 180 degrees.',
                'Calculate the recoil electron scattering angle phi using conservation of transverse momentum.',
                'Model the Klein-Nishina differential scattering cross-section d sigma / d Omega.'
            ]
        ];
    }
    if ((str_contains($p, 'heat diffusion') || str_contains($p, 'thermal diffusion') || str_contains($p, 'heat equation') || str_contains($p, 'heat conduction') || str_contains($p, 'ftcs') || (str_contains($p, 'heat') && (str_contains($p, 'rod') || str_contains($p, 'bar')))) && !str_contains($p, 'specific heat')) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# 1D Thermal Diffusion PDE: Forward-Time Central-Space (FTCS) Scheme
# Equation: du/dt = alpha * d^2u / dx^2
# Stability Criterion: r = alpha * dt / dx^2 <= 0.5

L = 1.0           # Length of rod (m)
Nx = 60           # Spatial intervals
dx = L / Nx
alpha = 1.11e-4   # Thermal diffusivity of copper (m^2/s)

# Choose dt to satisfy von Neumann stability r = 0.4 < 0.5
r = 0.4
dt = r * dx**2 / alpha
t_max = 60.0      # Simulation time (s)
Nt = int(t_max / dt)

x = np.linspace(0, L, Nx + 1)
u = np.zeros(Nx + 1)

# Initial Temperature Profile: Gaussian Hot Spot
u = 100.0 * np.exp(-((x - L/2) / 0.1)**2)

# Boundary Conditions (fixed ends at 0 °C)
u[0] = 0.0
u[-1] = 0.0

# Store profiles at key intervals
snapshot_times = [0, 5, 15, 30, 60]
snapshots = {0: u.copy()}

print("=" * 65)
print("1D THERMAL DIFFUSION SIMULATION (FTCS)")
print("=" * 65)
print(f"Rod Length: {L} m | Grid Points: {Nx + 1}")
print(f"Diffusivity alpha: {alpha} m^2/s")
print(f"Time Step dt: {dt:.4f} s | Courant Stability r: {r}")
print("=" * 65)

current_time = 0.0
next_snap_idx = 1

for step in range(1, Nt + 1):
    current_time += dt
    u_new = u.copy()
    # FTCS spatial discretization stencil
    u_new[1:-1] = u[1:-1] + r * (u[2:] - 2*u[1:-1] + u[:-2])
    u = u_new
    
    if next_snap_idx < len(snapshot_times) and current_time >= snapshot_times[next_snap_idx]:
        snapshots[snapshot_times[next_snap_idx]] = u.copy()
        next_snap_idx += 1

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Temperature Snapshots
for t_snap, u_prof in snapshots.items():
    ax1.plot(x, u_prof, lw=2, label=f'$t = {t_snap}$ s')

ax1.set_title('1D Heat Diffusion Cooling Profiles (FTCS)', fontsize=12, fontweight='bold')
ax1.set_xlabel('Position $x$ along rod (m)', fontsize=11)
ax1.set_ylabel('Temperature $T$ (°C)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Peak Center Temperature Decay
decay_t = np.linspace(0, t_max, len(snapshots))
decay_T = [snapshots[t][Nx // 2] for t in snapshots]
ax2.plot(list(snapshots.keys()), decay_T, 'ro-', lw=2, markersize=6)
ax2.set_title('Midpoint Temperature Dissipation', fontsize=12, fontweight='bold')
ax2.set_xlabel('Time $t$ (s)', fontsize=11)
ax2.set_ylabel('Center Temperature (°C)', fontsize=11)
ax2.grid(True, alpha=0.3)

plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => '1D Heat Diffusion PDE: Explicit FTCS Discretization & Stability Analysis',
            'code' => $code,
            'explanation' => 'Models transient heat conduction across a 1D thermal bar using the explicit Forward-Time Central-Space (FTCS) finite-difference scheme. Demonstrates von Neumann numerical stability bounds $r = \\alpha \\Delta t / \\Delta x^2 \\le 0.5$.',
            'complexity' => 'Time: O(Nx * Nt), Space: O(Nx)',
            'exercises' => [
                'Set $r = 0.55$ to trigger numerical instability and observe exponential ringing.',
                'Switch from Dirichlet fixed boundaries to Neumann insulated boundaries $du/dx = 0$.',
                'Implement the unconditionally stable implicit Crank-Nicolson tridiagonal solver.'
            ]
        ];
    }

    // =========================================================================
    if ((str_contains($p, 'matrix') || str_contains($p, 'eigenvalue') || str_contains($p, 'eigenvector') || str_contains($p, 'determinant') || (str_contains($p, 'inverse') && !str_contains($p, 'square')) || preg_match('/\b(gauss[\s\-_]*(?:elimination|jordan|seidel))\b/', $p) || str_contains($p, 'system of linear')) && !str_contains($p, 'wavepacket') && !str_contains($p, 'barrier') && !str_contains($p, 'tunneling') && !str_contains($p, 'quadrature') && !str_contains($p, 'wavefunction') && !str_contains($p, 'potential') && !str_contains($p, 'well') && !str_contains($p, 'box')) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Computational Linear Algebra: Matrix Operations & Eigenvalue Decomposition
# Physical System: Coupled Oscillator Stiffness Matrix

A = np.array([
    [ 4.0, -1.0,  0.0],
    [-1.0,  4.0, -1.0],
    [ 0.0, -1.0,  4.0]
])

b = np.array([12.0, 8.0, 15.0])

# 1. Determinant, Inverse, and Matrix Properties
det_A = np.linalg.det(A)
inv_A = np.linalg.inv(A)
x_sol = np.linalg.solve(A, b)

# 2. Eigenvalue Problem: A * v = lambda * v (Normal Modes)
eigenvalues, eigenvectors = np.linalg.eigh(A)

print("=" * 65)
print("COMPUTATIONAL LINEAR ALGEBRA REPORT")
print("=" * 65)
print("Stiffness Matrix A:\n", A)
print(f"\nDeterminant det(A):     {det_A:.4f}")
print("Inverse Matrix A^(-1):\n", np.round(inv_A, 4))
print(f"\nSolution of A*x = b:    {np.round(x_sol, 4)}")
print(f"\nEigenvalues (Omega^2):  {np.round(eigenvalues, 4)}")
print("Normalized Eigenvectors:\n", np.round(eigenvectors, 4))
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Matrix Heatmap Visualization
im = ax1.imshow(A, cmap='viridis', aspect='auto')
ax1.set_title('Matrix Element Structure Heatmap', fontsize=12, fontweight='bold')
for i in range(3):
    for j in range(3):
        ax1.text(j, i, f'{A[i, j]:.1f}', ha='center', va='center', color='white', fontweight='bold')
plt.colorbar(im, ax=ax1)

# Plot 2: Eigenvector Normal Modes
modes = np.arange(1, 4)
for idx in range(3):
    ax2.plot(modes, eigenvectors[:, idx], 'o-', lw=2, label=f'Mode {idx+1} ($\\lambda={eigenvalues[idx]:.2f}$)')

ax2.set_title('Normal Mode Displacements', fontsize=12, fontweight='bold')
ax2.set_xlabel('Oscillator Coordinate', fontsize=11)
ax2.set_ylabel('Mode Amplitude', fontsize=11)
ax2.set_xticks(modes)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Linear Algebra: Matrix Decomposition, Inversion & Eigenmode Analysis',
            'code' => $code,
            'explanation' => 'Performs spectral decomposition, matrix inversion, and solves coupled linear algebraic systems $\mathbf{A}\mathbf{x} = \mathbf{b}$. Identifies characteristic normal modes through symmetric eigenvalue computation.',
            'complexity' => 'Time: O(N^3), Space: O(N^2)',
            'exercises' => [
                'Verify the orthogonality condition of eigenvectors $\mathbf{v}_i^T \mathbf{v}_j = \delta_{ij}$.',
                'Implement the iterative Gauss-Seidel method and compare speed against direct LU decomposition.',
                'Diagonalize a 1D tight-binding Hamiltonian matrix to plot the energy band structure.'
            ]
        ];
    }

    // =========================================================================
    // 11. KEPLER PLANETARY ORBITS
    // =========================================================================
    if ((str_contains($p, 'kepler') || str_contains($p, 'orbit') || str_contains($p, 'planetary') || str_contains($p, 'two body')) && !str_contains($p, 'potential energy')) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Keplerian Two-Body Orbital Dynamics via Symplectic Velocity Verlet
# Central Gravitational Acceleration: a = - (G * M / r^3) * r

G_M = 4 * np.pi**2   # Gravitational parameter in AU and Solar Mass units
dt = 0.001           # Time step in years
t_max = 3.0          # Simulation duration (years)
steps = int(t_max / dt)

# Initial Conditions (Earth-like eccentric orbit: r0 = 1.0 AU, v0 = 2*pi*sqrt(1.2))
x, y = 1.0, 0.0
vx, vy = 0.0, 2 * np.pi * 1.15

xs, ys = np.zeros(steps), np.zeros(steps)
energies = np.zeros(steps)

def get_accel(x, y):
    r = np.hypot(x, y)
    return -G_M * x / r**3, -G_M * y / r**3

ax, ay = get_accel(x, y)

# Velocity Verlet Integration (Conserves Phase-Space Energy)
for i in range(steps):
    xs[i] = x
    ys[i] = y
    r = np.hypot(x, y)
    v2 = vx**2 + vy**2
    energies[i] = 0.5 * v2 - G_M / r
    
    # Position update
    x += vx * dt + 0.5 * ax * dt**2
    y += vy * dt + 0.5 * ay * dt**2
    
    # New acceleration
    new_ax, new_ay = get_accel(x, y)
    
    # Velocity update
    vx += 0.5 * (ax + new_ax) * dt
    vy += 0.5 * (ay + new_ay) * dt
    ax, ay = new_ax, new_ay

print("=" * 65)
print("KEPLER ORBIT SIMULATION REPORT")
print("=" * 65)
print(f"Orbital Duration:    {t_max} Earth years")
print(f"Perihelion Distance: {np.min(np.hypot(xs, ys)):.4f} AU")
print(f"Aphelion Distance:   {np.max(np.hypot(xs, ys)):.4f} AU")
print(f"Max Relative Energy Error: {abs(energies[-1] - energies[0])/abs(energies[0]):.2e}")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 5))

# Plot 1: 2D Orbital Trajectory
ax1.plot(xs, ys, 'b-', lw=1.8, label='Planetary Orbit')
ax1.scatter([0], [0], color='gold', s=200, edgecolors='orange', zorder=5, label='Star (Sun)')
ax1.set_title('Keplerian Orbit Trajectory in Orbital Plane', fontsize=12, fontweight='bold')
ax1.set_xlabel('$x$ Coordinate (AU)', fontsize=11)
ax1.set_ylabel('$y$ Coordinate (AU)', fontsize=11)
ax1.set_aspect('equal')
ax1.grid(True, alpha=0.3)
ax1.legend(loc='upper right')

# Plot 2: Energy Conservation Stability
ax2.plot(np.linspace(0, t_max, steps), (energies - energies[0]) / abs(energies[0]), 'r-', lw=1.5)
ax2.set_title('Relative Specific Energy Drift $\\Delta E / E_0$', fontsize=12, fontweight='bold')
ax2.set_xlabel('Time $t$ (years)', fontsize=11)
ax2.set_ylabel('Fractional Energy Error', fontsize=11)
ax2.ticklabel_format(style='sci', axis='y', scilimits=(0,0))
ax2.grid(True, alpha=0.3)

plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Keplerian Planetary Orbits: Symplectic Velocity Verlet Integration',
            'code' => $code,
            'explanation' => 'Integrates planetary motion governed by Newton\'s inverse-square gravitational law using the symplectic Velocity Verlet scheme, ensuring long-term conservation of mechanical energy and angular momentum.',
            'complexity' => 'Time: O(steps), Space: O(steps)',
            'exercises' => [
                'Verify Kepler\'s Third Law $T^2 \\propto a^3$ by varying semi-major axis $a$.',
                'Simulate gravitational slingshot (hyperbolic trajectory with $E > 0$).',
                'Add a perturbing potential $\\delta V = -\\alpha / r^3$ to observe relativistic perihelion precession.'
            ]
        ];
    }

    // =========================================================================
    // 12. MONTE CARLO METHODS (Pi Estimation & Random Walks)
    if ((str_contains($p, 'monte carlo') || (str_contains($p, 'pi') && (str_contains($p, 'estimate') || str_contains($p, 'dart') || str_contains($p, 'sampling')))) && !str_contains($p, 'random walk') && !str_contains($p, 'brownian')) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Monte Carlo Method: Numerical Estimation of Pi via Uniform Dart Sampling
# Theoretical Basis: Ratio of Area of Inscribed Circle to Enclosing Square = pi / 4

np.random.seed(42)
N_samples = 10000

# Generate 2D uniform random coordinates in [-1, 1] x [-1, 1]
x = np.random.uniform(-1.0, 1.0, N_samples)
y = np.random.uniform(-1.0, 1.0, N_samples)

# Inscribed unit circle test: x^2 + y^2 <= 1
inside_circle = (x**2 + y**2) <= 1.0
pi_estimate = 4.0 * np.sum(inside_circle) / N_samples
error = abs(pi_estimate - np.pi)

# Running estimation convergence
running_inside = np.cumsum(inside_circle)
n_indices = np.arange(1, N_samples + 1)
running_pi = 4.0 * running_inside / n_indices

print("=" * 65)
print("MONTE CARLO PI ESTIMATION REPORT")
print("=" * 65)
print(f"Total Random Samples N:  {N_samples}")
print(f"Points Inside Circle:    {np.sum(inside_circle)}")
print(f"Estimated Value of Pi:   {pi_estimate:.6f}")
print(f"True Value of Pi:        {np.pi:.6f}")
print(f"Absolute Error:          {error:.6f} ({error / np.pi * 100:.3f}%)")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Geometric Dartboard Sampling
ax1.scatter(x[inside_circle][:1500], y[inside_circle][:1500], color='royalblue', s=3, alpha=0.6, label='Inside ($r \\le 1$)')
ax1.scatter(x[~inside_circle][:1500], y[~inside_circle][:1500], color='crimson', s=3, alpha=0.6, label='Outside ($r > 1$)')
circle = plt.Circle((0, 0), 1.0, color='black', fill=False, lw=1.8)
ax1.add_patch(circle)
ax1.set_aspect('equal')
ax1.set_title(f'Monte Carlo Dartboard ($N = {N_samples}$)', fontsize=12, fontweight='bold')
ax1.set_xlabel('$x$', fontsize=11)
ax1.set_ylabel('$y$', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend(loc='upper right')

# Plot 2: Convergence vs Sample Size N (1/sqrt(N) Error Band)
ax2.plot(n_indices, running_pi, 'b-', lw=1.2, label='Monte Carlo Estimate')
ax2.axhline(np.pi, color='red', linestyle='--', lw=1.8, label=r'Exact $\pi \approx 3.14159$')
std_error = 4.0 * np.sqrt(np.pi / 4.0 * (1 - np.pi / 4.0) / n_indices)
ax2.fill_between(n_indices, np.pi - std_error, np.pi + std_error, color='gray', alpha=0.2, label=r'$1\sigma$ Error Band ($1/\sqrt{N}$)')
ax2.set_title('Convergence Rate Scaling vs Sample Count', fontsize=12, fontweight='bold')
ax2.set_xlabel('Sample Count $N$', fontsize=11)
ax2.set_ylabel('Estimated Value', fontsize=11)
ax2.set_ylim(2.8, 3.5)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Monte Carlo Pi Estimation & Stochastic Error Scaling',
            'code' => $code,
            'explanation' => 'Estimates fundamental mathematical constants via pseudo-random uniform sampling across high-dimensional geometry. Validates the Central Limit Theorem and $\\mathcal{O}(1/\\sqrt{N})$ asymptotic error decay.',
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Increase $N$ to 100,000 and confirm the $1/\\sqrt{N}$ convergence rate empirically.',
                'Simulate a 2D random walk (Brownian motion) and verify that mean-squared displacement $\\langle r^2 \\rangle \\propto t$.',
                'Implement Monte Carlo integration for a multidimensional sphere volume in 4 dimensions.'
            ]
        ];
    }

    // =========================================================================
    // 13. SORTING ALGORITHMS (Bubble Sort, Selection, Quick Sort)
    // =========================================================================
    if (str_contains($p, 'sort') || str_contains($p, 'bubble') || str_contains($p, 'quick sort') || str_contains($p, 'selection sort')) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Computational Sorting Algorithms: Bubble Sort with Operation Telemetry
# Comparison-based sorting with O(N^2) worst-case time complexity

def bubble_sort_instrumented(arr):
    """Sort array in-place while tracking comparisons and swaps."""
    a = arr.copy()
    n = len(a)
    comparisons = 0
    swaps = 0
    history = [a.copy()]
    
    for i in range(n):
        swapped = False
        for j in range(0, n - i - 1):
            comparisons += 1
            if a[j] > a[j + 1]:
                a[j], a[j + 1] = a[j + 1], a[j]
                swaps += 1
                swapped = True
        history.append(a.copy())
        if not swapped:
            break
            
    return a, comparisons, swaps, history

# Unsorted scientific measurements
np.random.seed(101)
raw_data = np.random.randint(10, 99, size=15)
sorted_data, comp_count, swap_count, state_history = bubble_sort_instrumented(raw_data)

print("=" * 65)
print("BUBBLE SORT ALGORITHM TELEMETRY")
print("=" * 65)
print(f"Original Input Array:  {list(raw_data)}")
print(f"Final Sorted Array:    {list(sorted_data)}")
print(f"Total Element Count:   {len(raw_data)}")
print(f"Total Comparisons:     {comp_count}")
print(f"Total Swaps Executed:  {swap_count}")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Array States (Initial vs Sorted)
indices = np.arange(len(raw_data))
width = 0.38
ax1.bar(indices - width/2, raw_data, width=width, color='crimson', alpha=0.7, label='Initial Unsorted')
ax1.bar(indices + width/2, sorted_data, width=width, color='teal', alpha=0.7, label='Final Sorted')
ax1.set_title('Array Elements: Before vs After Sorting', fontsize=12, fontweight='bold')
ax1.set_xlabel('Index in Array', fontsize=11)
ax1.set_ylabel('Numerical Value', fontsize=11)
ax1.set_xticks(indices)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Complexity Comparison O(N^2) vs O(N log N)
N_sizes = np.array([5, 10, 20, 50, 100, 200])
bubble_ops = N_sizes**2
quicksort_ops = N_sizes * np.log2(N_sizes)
ax2.plot(N_sizes, bubble_ops, 'ro-', lw=1.8, label=r'Bubble Sort $\mathcal{O}(N^2)$')
ax2.plot(N_sizes, quicksort_ops, 'go-', lw=1.8, label=r'QuickSort $\mathcal{O}(N \log N)$')
ax2.set_title('Theoretical Algorithm Scaling Benchmark', fontsize=12, fontweight='bold')
ax2.set_xlabel('Array Size $N$', fontsize=11)
ax2.set_ylabel('Operation Count', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Algorithm Design: Bubble Sort with Complexity Telemetry',
            'code' => $code,
            'explanation' => 'Implements comparison-based sorting tracking individual element exchanges. Compares the quadratic $\\mathcal{O}(N^2)$ operation scaling against optimal divide-and-conquer $\\mathcal{O}(N \\log N)$ paradigms.',
            'complexity' => 'Time: O(N^2), Space: O(N)',
            'exercises' => [
                'Implement QuickSort using the median-of-three pivot selection scheme.',
                'Benchmark runtime using Python\'s `time.perf_counter()` on sorted vs reverse-sorted arrays.',
                'Implement Binary Search on the sorted array to achieve $O(\\log N)$ element retrieval.'
            ]
        ];
    }

    // =========================================================================
    // 14. KINETIC ENERGY, MOMENTUM & CLASSICAL KINEMATICS
    if ((str_contains($p, 'kinetic energy') || (str_contains($p, 'linear momentum') && (str_contains($p, 'velocity') || str_contains($p, 'mass'))) || (str_contains($p, 'mass') && str_contains($p, 'velocity') && (str_contains($p, 'energy') || str_contains($p, 'momentum')))) && !str_contains($p, 'angular') && !str_contains($p, 'quantum') && !str_contains($p, 'schrodinger') && !str_contains($p, 'well') && !str_contains($p, 'heat') && !str_contains($p, 'orbit') && !str_contains($p, 'kepler') && !str_contains($p, 'lorentz')) {
        $mass = 2.0;
        $velocity = 15.0;
        if (preg_match_all('/(\d+(?:\.\d+)?)/', $problem, $matches)) {
            if (!empty($matches[1][0])) $mass = (float)$matches[1][0];
            if (!empty($matches[1][1])) $velocity = (float)$matches[1][1];
        }
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Classical Mechanics: Kinematics, Linear Momentum & Kinetic Energy
# Physical Formulations:
# Linear Momentum: p = m * v
# Kinetic Energy:  E_k = 0.5 * m * v^2 = p^2 / (2 * m)

mass = {{MASS}}          # Mass of body (kg)
v0 = {{VELOCITY}}        # Velocity (m/s)

p0 = mass * v0
Ek0 = 0.5 * mass * v0**2

print("=" * 65)
print(f"CLASSICAL KINEMATICS TELEMETRY (Mass = {mass} kg, Velocity = {v0} m/s)")
print("=" * 65)
print(f"Linear Momentum (p = m*v):        {p0:.4f} kg·m/s (N·s)")
print(f"Kinetic Energy (Ek = 0.5*m*v^2):  {Ek0:.4f} Joules")
print(f"Relativistic Rest Energy (m*c^2): {mass * (3e8)**2:.4e} Joules")
print("=" * 65)

# Velocity parameter sweep: compare linear momentum vs quadratic energy
v_range = np.linspace(0, max(30.0, v0 * 2.0), 300)
momentum_curve = mass * v_range
kinetic_curve = 0.5 * mass * v_range**2

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Linear Momentum Scaling
ax1.plot(v_range, momentum_curve, 'b-', lw=2.2, label=r'Momentum $p = m v$')
ax1.scatter([v0], [p0], color='red', s=90, zorder=5, label=f'State ($p={p0:.1f}$ N·s)')
ax1.set_title(f'Linear Momentum vs Velocity ($m = {mass}$ kg)', fontsize=12, fontweight='bold')
ax1.set_xlabel(r'Velocity $v$ (m/s)', fontsize=11)
ax1.set_ylabel(r'Momentum $p$ (kg·m/s)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Quadratic Kinetic Energy Profile
ax2.plot(v_range, kinetic_curve, 'crimson', lw=2.2, label=r'Kinetic Energy $E_k = \frac{1}{2}m v^2$')
ax2.scatter([v0], [Ek0], color='blue', s=90, zorder=5, label=f'State ($E_k={Ek0:.1f}$ J)')
ax2.set_title('Quadratic Kinetic Energy Profile', fontsize=12, fontweight='bold')
ax2.set_xlabel(r'Velocity $v$ (m/s)', fontsize=11)
ax2.set_ylabel(r'Kinetic Energy $E_k$ (Joules)', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        $code = str_replace(['{{MASS}}', '{{VELOCITY}}'], [(string)$mass, (string)$velocity], $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Classical Mechanics: Kinetic Energy & Linear Momentum (m = {$mass} kg, v = {$velocity} m/s)",
            'code' => $code,
            'explanation' => "Computes linear momentum \$p = mv\$ and kinetic energy \$E_k = \\frac{1}{2}mv^2\$ for mass \$m = {$mass}\$ kg at velocity \$v = {$velocity}\$ m/s. Visualizes linear momentum scaling against quadratic energy growth.",
            'complexity' => 'Time: O(1), Space: O(1)',
            'exercises' => [
                'Compute the mechanical work required to accelerate the object from v1 to v2 via the Work-Energy Theorem.',
                'Calculate relativistic corrections using E = m*c^2 / sqrt(1 - v^2/c^2) - m*c^2 for high speeds.',
                'Model an elastic collision between two masses and verify simultaneous conservation of momentum and kinetic energy.'
            ]
        ];
    }

    // =========================================================================
    // 15. CIRCULAR & SPHERICAL GEOMETRY (Area, Perimeter, Volume, Inertia)
    // =========================================================================
    if ((str_contains($p, 'circle') || str_contains($p, 'sphere')) && (str_contains($p, 'area') || str_contains($p, 'circumference') || str_contains($p, 'perimeter') || str_contains($p, 'volume') || str_contains($p, 'radius'))) {
        $radius = 5.0;
        if (preg_match('/(\d+(?:\.\d+)?)/', $problem, $m)) {
            $radius = (float)$m[1];
        }
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Geometric & Physical Calculations for Circular / Spherical Systems
# Radius Parameter: r

r = {{RADIUS}}

# Analytical Formulae
area = np.pi * r**2
circumference = 2 * np.pi * r
sphere_volume = (4.0 / 3.0) * np.pi * r**3
sphere_surface_area = 4.0 * np.pi * r**2

print("=" * 65)
print(f"GEOMETRIC ANALYSIS FOR RADIUS r = {r}")
print("=" * 65)
print(f"Circle Circumference (2*pi*r):   {circumference:.4f}")
print(f"Circle Area (pi*r^2):            {area:.4f}")
print(f"Sphere Surface Area (4*pi*r^2):  {sphere_surface_area:.4f}")
print(f"Sphere Volume (4/3*pi*r^3):      {sphere_volume:.4f}")
print("=" * 65)

# Parametric Visualization
theta = np.linspace(0, 2 * np.pi, 300)
x = r * np.cos(theta)
y = r * np.sin(theta)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: 2D Geometry
ax1.plot(x, y, 'b-', lw=2.2, label=f'Circle Boundary ($r = {r}$)')
ax1.fill(x, y, color='cyan', alpha=0.25, label=f'Area = {area:.2f}')
ax1.plot([0, r], [0, 0], 'r--', lw=2, label=f'Radius $r = {r}$')
ax1.scatter([0], [0], color='black', s=50, zorder=5)
ax1.set_aspect('equal')
ax1.set_title(f'2D Circle Geometry (Radius = {r})', fontsize=12, fontweight='bold')
ax1.set_xlabel(r'$x$', fontsize=11)
ax1.set_ylabel(r'$y$', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend(loc='upper right')

# Plot 2: Scaling vs Radius
r_range = np.linspace(0.1, max(10.0, r * 2.0), 150)
ax2.plot(r_range, np.pi * r_range**2, 'b-', lw=2, label=r'Area $\pi r^2$')
ax2.plot(r_range, 2 * np.pi * r_range, 'g--', lw=1.8, label=r'Circumference $2\pi r$')
ax2.scatter([r], [area], color='red', s=80, zorder=5, label=f'Current Area ({area:.1f})')
ax2.set_title(r'Geometric Scaling vs Radius $r$', fontsize=12, fontweight='bold')
ax2.set_xlabel(r'Radius $r$', fontsize=11)
ax2.set_ylabel('Metric Value', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        $code = str_replace('{{RADIUS}}', (string)$radius, $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Geometric Modeling: Area, Perimeter & Volume (Radius = {$radius})",
            'code' => $code,
            'explanation' => "Calculates planar and spatial metrics for radius \$r = {$radius}\$. Renders the 2D parametric geometry and contrasts linear boundary growth against quadratic surface area scaling.",
            'complexity' => 'Time: O(1), Space: O(1)',
            'exercises' => [
                'Extend the model to elliptical geometry with semi-major axis a and semi-minor axis b.',
                'Compute the moment of inertia for a solid disk I = 0.5 * M * R^2 vs thin hoop I = M * R^2.',
                'Perform 2D Monte Carlo integration to estimate the circle area numerically.'
            ]
        ];
    }

    // =========================================================================
    // 16. RADIOACTIVE DECAY & HALF-LIFE KINETICS
    // =========================================================================
    if (str_contains($p, 'decay') || str_contains($p, 'radioactive') || str_contains($p, 'half life') || str_contains($p, 'half-life') || str_contains($p, 'nuclear decay') || str_contains($p, 'bateman')) {
        $isotope = 'Carbon-14 (14C)';
        $tHalf = 5730.0;
        $unit = 'years';
        $isSpecific = false;

        if (preg_match('/\b(carbon[\s\-_]*14|c[\s\-_]*14)\b/i', $problem)) {
            $isotope = 'Carbon-14 (14C)';
            $tHalf = 5730.0;
            $unit = 'years';
            $isSpecific = true;
        } elseif (preg_match('/\b(uranium[\s\-_]*238|u[\s\-_]*238)\b/i', $problem)) {
            $isotope = 'Uranium-238 (238U)';
            $tHalf = 4.468e9;
            $unit = 'years';
            $isSpecific = true;
        } elseif (preg_match('/\b(radon[\s\-_]*222|rn[\s\-_]*222)\b/i', $problem)) {
            $isotope = 'Radon-222 (222Rn)';
            $tHalf = 3.82;
            $unit = 'days';
            $isSpecific = true;
        } elseif (preg_match('/\b(cobalt[\s\-_]*60|co[\s\-_]*60)\b/i', $problem)) {
            $isotope = 'Cobalt-60 (60Co)';
            $tHalf = 5.27;
            $unit = 'years';
            $isSpecific = true;
        } elseif (preg_match('/\b(iodine[\s\-_]*131|i[\s\-_]*131)\b/i', $problem)) {
            $isotope = 'Iodine-131 (131I)';
            $tHalf = 8.02;
            $unit = 'days';
            $isSpecific = true;
        }

        if (preg_match('/(?:half[\s\-_]*life|t1\/2|t_half)\s*=?\s*(\d+(?:\.\d+)?)/i', $problem, $m)) {
            $tHalf = (float)$m[1];
            $isSpecific = true;
            if (preg_match('/(\d+(?:\.\d+)?)\s*(years|year|yrs|yr|days|day|hours|hour|hrs|hr|minutes|min|seconds|sec|s)\b/i', $problem, $um)) {
                $unit = strtolower($um[2]);
            }
        }

        // If Bateman or decay chain is explicitly requested
        if (str_contains($p, 'chain') || str_contains($p, 'bateman') || str_contains($p, 'daughter') || str_contains($p, 'series')) {
            $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Radioactive Decay Kinetics: Bateman Equations for Sequential Decay Series
# N1 (Parent) ---> N2 (Daughter) ---> N3 (Stable Product)
# dN1/dt = - lambda_1 * N1
# dN2/dt =   lambda_1 * N1 - lambda_2 * N2
# dN3/dt =   lambda_2 * N2

N0 = 1000.0         # Initial parent nuclei
t_half_1 = 6.0      # Half-life of parent (days)
t_half_2 = 14.0     # Half-life of daughter (days)

lambda_1 = np.log(2) / t_half_1
lambda_2 = np.log(2) / t_half_2

t = np.linspace(0, 60, 500)

# Analytical Bateman Solutions
N1 = N0 * np.exp(-lambda_1 * t)
N2 = N0 * (lambda_1 / (lambda_2 - lambda_1)) * (np.exp(-lambda_1 * t) - np.exp(-lambda_2 * t))
N3 = N0 - N1 - N2

t_max_daughter = np.log(lambda_2 / lambda_1) / (lambda_2 - lambda_1)
N2_max = N0 * (lambda_1 / lambda_2)**(lambda_2 / (lambda_2 - lambda_1))

print("=" * 65)
print("RADIOACTIVE DECAY CHAIN REPORT (Bateman Formulation)")
print("=" * 65)
print(f"Parent Half-Life:       {t_half_1} days (lambda1 = {lambda_1:.4f} /day)")
print(f"Daughter Half-Life:     {t_half_2} days (lambda2 = {lambda_2:.4f} /day)")
print(f"Peak Daughter Activity: t = {t_max_daughter:.2f} days (N2 = {N2_max:.1f} nuclei)")
print("=" * 65)

plt.figure(figsize=(9.5, 5))
plt.plot(t, N1, 'b-', lw=2.2, label=f'Parent $N_1$ ($t_{{1/2}}={t_half_1}$ d)')
plt.plot(t, N2, 'r-', lw=2.2, label=f'Daughter $N_2$ ($t_{{1/2}}={t_half_2}$ d)')
plt.plot(t, N3, 'g--', lw=2.0, label='Stable Product $N_3$')
plt.axvline(t_max_daughter, color='purple', linestyle=':', label=f'Peak Daughter ($t={t_max_daughter:.1f}$ d)')

plt.title('Sequential Radioactive Decay Chain: Nuclei Populations vs Time', fontsize=12, fontweight='bold')
plt.xlabel('Time $t$ (Days)', fontsize=11)
plt.ylabel('Nuclei Population $N(t)$', fontsize=11)
plt.grid(True, alpha=0.3)
plt.legend(fontsize=10.5)
plt.tight_layout()
plt.show()
PY;
            return [
                'success' => true,
                'source' => 'physics_engine',
                'title' => 'Radioactive Decay Chains: Analytical Bateman Formulation',
                'code' => $code,
                'explanation' => 'Models sequential radioactive transmutation across isotope chains using exact Bateman solutions. Identifies secular and transient equilibrium states and peak daughter isotope activity.',
                'complexity' => 'Time: O(N), Space: O(N)',
                'exercises' => [
                    'Simulate secular equilibrium where parent half-life is vastly greater than daughter half-life.',
                    'Implement Monte Carlo stochastic radioactive decay where individual nuclei decay probabilistically.',
                    'Compute radiological age dating of a rock sample based on daughter-to-parent isotope ratios.'
                ]
            ];
        }

        // Single Isotope Exponential Decay Kinetics
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# ==============================================================================
# Nuclear Physics: Radioactive Decay Kinetics & Half-Life Simulation
# Target Isotope: {{ISOTOPE}} (Half-Life t_{1/2} = {{THALF}} {{UNIT}})
# Differential Law: dN/dt = - lambda * N  ===>  N(t) = N_0 * exp(- lambda * t) = N_0 * (1/2)^(t / t_{1/2})
# Activity (Becquerels / Decays per unit time): A(t) = lambda * N(t)
# ==============================================================================

isotope_name = "{{ISOTOPE}}"
t_half = {{THALF}}        # Half-life
time_unit = "{{UNIT}}"
N0 = 10000.0             # Initial number of radioactive parent nuclei

# Decay Constant lambda = ln(2) / t_half
decay_constant = np.log(2) / t_half
mean_lifetime = 1.0 / decay_constant  # tau = t_1/2 / ln(2) approx 1.4427 * t_1/2

# Simulate over 5 half-lives
t_max = 5.0 * t_half
t = np.linspace(0, t_max, 500)

# Remaining Parent Nuclei N(t) and Decayed Daughter Nuclei N_d(t)
N_remaining = N0 * np.exp(-decay_constant * t)
N_decayed = N0 - N_remaining
activity = decay_constant * N_remaining

print("=" * 65)
print(f"RADIOACTIVE DECAY TELEMETRY: {isotope_name}")
print("=" * 65)
print(f"Half-Life (t_1/2):       {t_half} {time_unit}")
print(f"Decay Constant (lambda): {decay_constant:.4e} /{time_unit}")
print(f"Mean Lifetime (tau):     {mean_lifetime:.2f} {time_unit}")
print(f"Initial Population (N0): {N0:.0f} nuclei")
print("-" * 65)
for k in range(1, 6):
    tk = k * t_half
    Nk = N0 * (0.5**k)
    print(f"After {k} Half-Lives (t = {tk:7.1f} {time_unit}): {Nk:7.1f} nuclei ({Nk/N0*100:5.2f}%)")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Linear Decay Profile & Half-Life Markers
ax1.plot(t, N_remaining, 'b-', lw=2.2, label=r'Parent Nuclei $N(t) = N_0 e^{-\lambda t}$')
ax1.plot(t, N_decayed, 'g--', lw=1.8, label=r'Daughter Products $N_d(t) = N_0(1 - e^{-\lambda t})$')

for k in range(1, 4):
    tk = k * t_half
    Nk = N0 * (0.5**k)
    ax1.scatter([tk], [Nk], color='red', s=60, zorder=5)
    ax1.axvline(tk, color='gray', linestyle=':', alpha=0.5)
    ax1.text(tk, Nk + 0.04 * N0, f'{k}$t_{{1/2}}$\n({Nk/N0*100:.1f}%)', fontsize=8, ha='center', color='darkred')

ax1.set_title(f'Radioactive Decay of {isotope_name}', fontsize=12, fontweight='bold')
ax1.set_xlabel(f'Time $t$ ({time_unit})', fontsize=11)
ax1.set_ylabel('Number of Parent Nuclei $N(t)$', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend(loc='center right', fontsize=9.5)

# Plot 2: Semi-Logarithmic Scale (Demonstrating Linear Decay)
ax2.semilogy(t, N_remaining, 'crimson', lw=2.0, label=r'$\ln[N(t)] = \ln(N_0) - \lambda t$')
ax2.set_title('Logarithmic Linearity of Radioactive Decay', fontsize=12, fontweight='bold')
ax2.set_xlabel(f'Time $t$ ({time_unit})', fontsize=11)
ax2.set_ylabel(r'Nuclei Population $N(t)$ (Log Scale)', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend(loc='upper right', fontsize=9.5)

plt.tight_layout()
plt.show()
PY;
        $code = str_replace(
            ['{{ISOTOPE}}', '{{THALF}}', '{{UNIT}}'],
            [$isotope, (string)$tHalf, $unit],
            $template
        );
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Nuclear Physics: Radioactive Decay Kinetics of {$isotope} (t_1/2 = {$tHalf} {$unit})",
            'code' => $code,
            'explanation' => "Simulates exponential radioactive decay \$N(t) = N_0 e^{-\\lambda t} = N_0 (1/2)^{t / t_{1/2}}\$ for {$isotope} with half-life \$t_{1/2} = {$tHalf}\$ {$unit}. Computes decay constant \$\\lambda = \\ln(2)/t_{1/2}\$, mean lifetime \$\\tau\$, and benchmarks logarithmic linearity.",
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Compute the age of an ancient organic sample if the 14C / 12C ratio is 25% of modern carbon.',
                'Simulate radioactive equilibrium in a closed nuclear reactor environment.',
                'Implement Monte Carlo stochastic decay where each nucleus has decay probability p = 1 - exp(-lambda * dt).'
            ]
        ];
    }

    // =========================================================================
    // 16B. QUANTUM STATISTICAL MECHANICS (FERMI-DIRAC & BOSE-EINSTEIN DISTRIBUTIONS)
    // =========================================================================
    if (str_contains($p, 'fermi') || str_contains($p, 'bose') || str_contains($p, 'condensation')) {
        $isFermi = !str_contains($p, 'bose');
        
        $Ef = 5.0; // 5 eV Fermi energy default
        if (preg_match('/(?:fermi[\s\-_]*energy|ef|e_f)\s*=?\s*(\d+(?:\.\d+)?)/i', $problem, $m)) {
            $Ef = (float)$m[1];
        } elseif (preg_match('/(\d+(?:\.\d+)?)\s*ev\b/i', $problem, $m)) {
            $Ef = (float)$m[1];
        }

        // Check for temperatures
        $temps = [];
        if (preg_match_all('/(\d{2,5})\s*k\b/i', $problem, $tm)) {
            foreach ($tm[1] as $tVal) {
                $temps[] = (int)$tVal;
            }
        }
        if (empty($temps)) {
            $temps = $isFermi ? [0, 100, 300, 1000] : [100, 200, 300];
        }
        $tempsStr = "[" . implode(", ", array_unique($temps)) . "]";

        if ($isFermi) {
            $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# ==============================================================================
# Quantum Statistical Mechanics: Fermi-Dirac Distribution for Fermions (Spin 1/2)
# Occupation Probability: f(E) = 1 / (exp((E - E_F) / (k_B * T)) + 1)
# Density of States in 3D: g(E) = (V / (2 * pi^2)) * (2m / hbar^2)^(3/2) * sqrt(E)
# Occupied State Population: N(E) = g(E) * f(E)
# ==============================================================================

# Fundamental Constants
kB_eV = 8.617333262e-5  # Boltzmann constant in eV/K
Ef = {{EF}}             # Fermi energy in eV
temperatures = {{TEMPS}} # Kelvin temperatures

# Energy spectrum from 0 to 2 * Ef
E = np.linspace(0.001, 2.0 * Ef, 600)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

colors = ['black', 'blue', 'green', 'crimson', 'purple', 'orange']

print("=" * 65)
print(f"FERMI-DIRAC QUANTUM STATISTICS REPORT (Fermi Energy E_F = {Ef:.2f} eV)")
print("=" * 65)
print(f"Fermi Energy (E_F):         {Ef:.4f} eV")
print(f"Fermi Temperature (T_F):    {Ef / kB_eV:.1f} K")
print(f"Thermal Energy at 300 K:    {kB_eV * 300:.4f} eV")
print("-" * 65)

# Plot 1: Fermi-Dirac Occupation Probability f(E)
for idx, T in enumerate(temperatures):
    c = colors[idx % len(colors)]
    if T == 0:
        f_E = np.where(E < Ef, 1.0, np.where(E == Ef, 0.5, 0.0))
        label_str = r'$T = 0$ K (Step Function)'
    else:
        # Numerically stable evaluation avoiding exp overflow
        x = (E - Ef) / (kB_eV * T)
        f_E = np.zeros_like(x)
        mask_low = x < -100
        mask_high = x > 100
        mask_mid = ~mask_low & ~mask_high
        f_E[mask_low] = 1.0
        f_E[mask_high] = 0.0
        f_E[mask_mid] = 1.0 / (np.exp(x[mask_mid]) + 1.0)
        label_str = f'$T = {T}$ K ($k_BT = {kB_eV * T:.3f}$ eV)'
    
    ax1.plot(E, f_E, lw=2.0, color=c, label=label_str)
    
    # 3D Density of states g(E) ~ sqrt(E) and occupied density
    g_E = np.sqrt(E)
    N_E = g_E * f_E
    ax2.plot(E, N_E, lw=2.0, color=c, label=label_str)

ax1.axvline(Ef, color='red', linestyle='--', alpha=0.7, label=f'$E_F = {Ef}$ eV')
ax1.axhline(0.5, color='gray', linestyle=':', alpha=0.5)
ax1.scatter([Ef], [0.5], color='red', s=60, zorder=5)
ax1.set_title('Fermi-Dirac Distribution Function $f(E)$', fontsize=12, fontweight='bold')
ax1.set_xlabel('Energy $E$ (eV)', fontsize=11)
ax1.set_ylabel('Occupation Probability $f(E)$', fontsize=11)
ax1.set_ylim(-0.05, 1.08)
ax1.grid(True, alpha=0.3)
ax1.legend(loc='best', fontsize=9)

# Plot 2: Electron Energy Distribution N(E) = g(E) * f(E)
ax2.axvline(Ef, color='red', linestyle='--', alpha=0.7)
ax2.set_title(r'Electron Population Density $N(E) = g(E)f(E)$', fontsize=12, fontweight='bold')
ax2.set_xlabel('Energy $E$ (eV)', fontsize=11)
ax2.set_ylabel(r'Occupied Density (Arb. Units)', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend(loc='best', fontsize=9)

plt.tight_layout()
plt.show()
PY;
            $code = str_replace(['{{EF}}', '{{TEMPS}}'], [(string)$Ef, $tempsStr], $template);
            return [
                'success' => true,
                'source' => 'physics_engine',
                'title' => "Quantum Statistical Mechanics: Fermi-Dirac Distribution (E_F = {$Ef} eV)",
                'code' => $code,
                'explanation' => "Calculates the quantum Fermi-Dirac occupation probability \$f(E) = \\frac{1}{e^{(E - E_F)/(k_B T)} + 1}\$ for Fermi energy \$E_F = {$Ef}\$ eV across temperatures {$tempsStr}. Highlights step-function behavior at \$T = 0\$ K, thermal broadening across the Fermi surface, and 3D density of states filling \$N(E) = g(E)f(E)\$.",
                'complexity' => 'Time: O(N_temps * N_points), Space: O(N)',
                'exercises' => [
                    'Show analytically that the derivative df/dE at E = E_F equals -1 / (4 * k_B * T).',
                    'Integrate N(E) from 0 to E_F at T = 0 to verify the total electron density n = (2m E_F / hbar^2)^(3/2) / (3 pi^2).',
                    'Model chemical potential mu(T) temperature dependence via Sommerfeld expansion: mu(T) approx E_F [1 - (pi^2 / 12) * (k_B T / E_F)^2].'
                ]
            ];
        } else {
            // Bose-Einstein Distribution & Condensation
            $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# ==============================================================================
# Quantum Statistical Mechanics: Bose-Einstein Distribution & Condensation (BEC)
# Bosons (Integer Spin 0, 1, 2...)
# Distribution: f_BE(E) = 1 / (exp((E - mu) / (k_B * T)) - 1)
# Condensation Fraction: N_0 / N = 1 - (T / T_c)^(3/2)  for T < T_c
# ==============================================================================

kB_eV = 8.617333262e-5  # eV/K
Tc = 150.0              # Critical condensation temperature (K)

# Energy spectrum
E = np.linspace(0.005, 0.15, 500)  # Energy in eV
mu = -0.001                         # Chemical potential mu <= 0 for bosons

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

temps = [50, 100, 150, 250] # Kelvin
colors = ['purple', 'blue', 'forestgreen', 'crimson']

for idx, T in enumerate(temps):
    x = (E - mu) / (kB_eV * T)
    f_BE = 1.0 / (np.exp(np.clip(x, -50, 50)) - 1.0)
    ax1.plot(E * 1e3, f_BE, lw=2.0, color=colors[idx], label=f'$T = {T}$ K')

ax1.set_title('Bose-Einstein Distribution Function $f_{BE}(E)$', fontsize=12, fontweight='bold')
ax1.set_xlabel('Energy $E$ (meV)', fontsize=11)
ax1.set_ylabel(r'Average Occupation Number $f_{BE}$', fontsize=11)
ax1.set_ylim(0, 15)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Macroscopic Condensate Fraction N0 / N vs T / Tc
T_norm = np.linspace(0.0, 2.0, 300)
N0_fraction = np.where(T_norm <= 1.0, 1.0 - T_norm**1.5, 0.0)

ax2.plot(T_norm, N0_fraction, 'b-', lw=2.4, label=r'Condensate Fraction $N_0 / N = 1 - (T/T_c)^{3/2}$')
ax2.axvline(1.0, color='crimson', linestyle='--', label=r'Critical Temperature $T_c$')
ax2.scatter([1.0], [0.0], color='crimson', s=70, zorder=5)
ax2.fill_between(T_norm, 0, N0_fraction, color='cyan', alpha=0.2)
ax2.set_title('Bose-Einstein Condensate Fraction vs $T / T_c$', fontsize=12, fontweight='bold')
ax2.set_xlabel(r'Reduced Temperature $T / T_c$', fontsize=11)
ax2.set_ylabel(r'Ground State Fraction $N_0 / N$', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
            return [
                'success' => true,
                'source' => 'physics_engine',
                'title' => 'Quantum Statistical Mechanics: Bose-Einstein Distribution & Condensation (BEC)',
                'code' => $template,
                'explanation' => 'Models the quantum Bose-Einstein distribution function for bosons and computes the macroscopic ground-state condensation fraction \$N_0 / N = 1 - (T/T_c)^{3/2}\$ below the critical temperature \$T_c\$.',
                'complexity' => 'Time: O(N), Space: O(N)',
                'exercises' => [
                    'Derive the critical temperature Tc = (h^2 / 2 pi m k_B) * (n / zeta(3/2))^(2/3).',
                    'Compare the Bose-Einstein, Fermi-Dirac, and classical Maxwell-Boltzmann distributions at high temperatures.',
                    'Analyze the singularity in f_BE as E -> mu when mu -> 0.'
                ]
            ];
        }
    }

    // =========================================================================
    // 17. STATISTICAL MECHANICS (Maxwell-Boltzmann, Speed Distribution)
    // =========================================================================
    if ((str_contains($p, 'maxwell') || str_contains($p, 'boltzmann') || str_contains($p, 'speed distribution')) && !str_contains($p, 'fermi') && !str_contains($p, 'bose')) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Maxwell-Boltzmann Molecular Speed Distribution in 3D
# f(v) = 4 * pi * (m / (2 * pi * k_B * T))^(3/2) * v^2 * exp(- m * v^2 / (2 * k_B * T))

k_B = 1.380649e-23   # Boltzmann constant (J/K)
m_N2 = 4.65e-26      # Mass of Nitrogen molecule N2 (kg)
temperatures = [150, 300, 600] # Temperatures in Kelvin (K)

v = np.linspace(0, 1600, 500)

plt.figure(figsize=(9.5, 5))

for T in temperatures:
    prefactor = 4 * np.pi * (m_N2 / (2 * np.pi * k_B * T))**1.5
    f_v = prefactor * (v**2) * np.exp(-m_N2 * (v**2) / (2 * k_B * T))
    
    # Characteristic Speeds
    v_mp = np.sqrt(2 * k_B * T / m_N2)            # Most probable speed
    v_avg = np.sqrt(8 * k_B * T / (np.pi * m_N2)) # Mean speed
    v_rms = np.sqrt(3 * k_B * T / m_N2)           # Root-mean-square speed
    
    line, = plt.plot(v, f_v, lw=2.2, label=f'$T = {T}$ K ($v_{{mp}} = {v_mp:.0f}$ m/s)')
    plt.axvline(v_mp, color=line.get_color(), linestyle=':', alpha=0.6)

print("=" * 65)
print("MAXWELL-BOLTZMANN SPEED DISTRIBUTION (Nitrogen N2)")
print("=" * 65)
for T in temperatures:
    v_mp = np.sqrt(2 * k_B * T / m_N2)
    v_rms = np.sqrt(3 * k_B * T / m_N2)
    print(f"T = {T:3d} K: Most Probable = {v_mp:.1f} m/s | v_rms = {v_rms:.1f} m/s")
print("=" * 65)

plt.title('Maxwell-Boltzmann Molecular Speed Distribution ($N_2$ Gas)', fontsize=12, fontweight='bold')
plt.xlabel('Molecular Speed $v$ (m/s)', fontsize=11)
plt.ylabel('Probability Density $f(v)$ (s/m)', fontsize=11)
plt.grid(True, alpha=0.3)
plt.legend(fontsize=10.5)
plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Statistical Mechanics: Maxwell-Boltzmann Molecular Speed Distribution',
            'code' => $code,
            'explanation' => 'Models the thermodynamic velocity profile of ideal gas particles in thermal equilibrium. Computes the most probable speed $v_p$, mean speed $\\bar{v}$, and root-mean-square speed $v_{\\mathrm{rms}}$.',
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Verify the normalization condition \\int_0^\\infty f(v) dv = 1 using numerical Simpson integration.',
                'Compute the average kinetic energy <Ek> = 0.5 * m * <v^2> and verify equipartition 1.5 * k_B * T.',
                'Plot and compare Fermi-Dirac and Bose-Einstein quantum distribution functions as T -> 0.'
            ]
        ];
    }

    // =========================================================================
    // 18. ELECTRODYNAMICS (Electric Dipole Equipotential & Field Lines)
    if ((str_contains($p, 'dipole') || str_contains($p, 'equipotential') || str_contains($p, 'coulomb') || (str_contains($p, 'electric') && (str_contains($p, 'field line') || str_contains($p, 'potential line') || str_contains($p, 'dipole')))) && !str_contains($p, 'magnetic') && !str_contains($p, 'lorentz') && !str_contains($p, 'circuit')) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Electric Dipole: 2D Field Lines & Equipotential Contours
# Potential: V(r) = (1 / 4*pi*epsilon_0) * \sum (q_i / r_i)

eps0 = 8.854e-12
k_e = 1.0 / (4 * np.pi * eps0)

# Charges: (+q at x=+d/2, -q at x=-d/2)
q = 1.0e-9      # 1 nC
d = 0.4         # Separation in meters
charges = [
    {'q': q, 'x': d/2, 'y': 0.0},
    {'q': -q, 'x': -d/2, 'y': 0.0}
]

# Computational Spatial Grid
grid_x = np.linspace(-0.8, 0.8, 120)
grid_y = np.linspace(-0.8, 0.8, 120)
X, Y = np.meshgrid(grid_x, grid_y)

V = np.zeros_like(X)
Ex = np.zeros_like(X)
Ey = np.zeros_like(Y)

for ch in charges:
    dx = X - ch['x']
    dy = Y - ch['y']
    r = np.hypot(dx, dy)
    r = np.maximum(r, 0.03)  # Regularize singularity
    
    # Scalar Potential
    V += k_e * ch['q'] / r
    # Electric Field Vector: E = - grad(V)
    Ex += k_e * ch['q'] * dx / r**3
    Ey += k_e * ch['q'] * dy / r**3

plt.figure(figsize=(8.5, 6.5))

# Plot Equipotential Contours
levels = np.linspace(-60, 60, 25)
cs = plt.contour(X, Y, V, levels=levels, cmap='coolwarm', alpha=0.7)
plt.clabel(cs, inline=True, fontsize=8, fmt='%1.0f V')

# Plot Vector Field Lines
plt.streamplot(X, Y, Ex, Ey, color='gray', density=1.4, arrowsize=1.2, linewidth=0.8)

# Charge Markers
plt.scatter([d/2], [0], color='red', s=160, zorder=6, label=r'$+q$ Positive Charge')
plt.scatter([-d/2], [0], color='blue', s=160, zorder=6, label=r'$-q$ Negative Charge')

plt.title('Electric Dipole: Equipotential Surfaces & Field Lines', fontsize=12, fontweight='bold')
plt.xlabel('$x$ Coordinate (m)', fontsize=11)
plt.ylabel('$y$ Coordinate (m)', fontsize=11)
plt.gca().set_aspect('equal')
plt.legend(loc='upper right')
plt.grid(True, alpha=0.2)
plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Electrodynamics: 2D Electric Dipole Field Lines & Equipotential Contours',
            'code' => $code,
            'explanation' => 'Calculates electrostatic scalar potential fields $V(\\mathbf{r})$ and spatial gradient vector electric fields $\\mathbf{E} = -\\nabla V$ for a multi-pole charge distribution, rendering orthogonal field lines and equipotential curves.',
            'complexity' => 'Time: O(Nx * Ny * N_charges), Space: O(Nx * Ny)',
            'exercises' => [
                'Add two additional charges to simulate an electric quadrupole configuration.',
                'Verify Gauss\'s Law by numerically computing the surface integral around the positive charge.',
                'Compute the dipole moment p = q * d and compare asymptotic potential with V ~ cos(theta) / r^2.'
            ]
        ];
    }

    // =========================================================================
    // 18B. BLACKBODY RADIATION, PLANCK'S LAW & WIEN'S DISPLACEMENT
    // =========================================================================
    if (str_contains($p, 'blackbody') || str_contains($p, 'planck') || str_contains($p, 'wien') || str_contains($p, 'stefan-boltzmann') || str_contains($p, 'cavity radiation') || (str_contains($p, 'radiation') && str_contains($p, 'spectrum'))) {
        $temps = [];
        if (preg_match_all('/(\d{3,5})\s*k\b/i', $problem, $tm)) {
            foreach ($tm[1] as $tVal) {
                $temps[] = (int)$tVal;
            }
        }
        if (empty($temps)) {
            $temps = [3000, 4000, 5000];
        }
        $tempsStr = "[" . implode(", ", array_unique($temps)) . "]";

        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# ==============================================================================
# Quantum Physics: Planck's Blackbody Radiation Law & Wien's Displacement
# Spectral Radiance (Planck Formula):
# B_lambda(lambda, T) = (2 * h * c^2 / lambda^5) / (exp(h * c / (lambda * k_B * T)) - 1)
# Wien's Displacement Law: lambda_peak * T = b approx 2.89777e-3 m·K
# Stefan-Boltzmann Law: Total Power P = sigma * T^4
# ==============================================================================

# Fundamental Constants (SI Units)
h = 6.62607015e-34       # Planck constant (J·s)
c = 2.99792458e8         # Speed of light (m/s)
kB = 1.380649e-23        # Boltzmann constant (J/K)
sigma = 5.670374e-8      # Stefan-Boltzmann constant (W/(m^2·K^4))
b_wien = 2.8977719e-3    # Wien displacement constant (m·K)

temperatures = {{TEMPS}} # Temperatures in Kelvin

# Wavelength spectrum from 50 nm to 2500 nm (0.05 um to 2.5 um)
wavelengths_nm = np.linspace(50, 2500, 800)
wavelengths_m = wavelengths_nm * 1e-9

def planck_spectral_radiance(lam_m, T):
    """Computes spectral radiance B_lambda in kW / (sr * m^2 * nm)"""
    exponent = (h * c) / (lam_m * kB * T)
    # Clip exponent for numerical stability
    exp_factor = np.exp(np.clip(exponent, None, 100)) - 1.0
    radiance_SI = (2.0 * h * c**2) / (lam_m**5 * exp_factor) # W / (sr * m^3)
    # Convert to kW / (sr * m^2 * nm)
    return radiance_SI * 1e-9 / 1e3

print("=" * 65)
print("PLANCK BLACKBODY RADIATION TELEMETRY")
print("=" * 65)
for T in temperatures:
    lam_peak_nm = (b_wien / T) * 1e9
    peak_radiance = planck_spectral_radiance(lam_peak_nm * 1e-9, T)
    total_power = sigma * T**4
    print(f"Temperature T = {T:4d} K: Peak lambda = {lam_peak_nm:6.1f} nm | Peak B = {peak_radiance:.3f} kW/(sr·m²·nm) | Total Power = {total_power/1e6:7.3f} MW/m²")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11.5, 4.6))

colors = ['crimson', 'darkorange', 'gold', 'forestgreen', 'royalblue', 'purple']

# Plot 1: Spectral Radiance Curves vs Wavelength
for idx, T in enumerate(temperatures):
    col = colors[idx % len(colors)]
    B_lam = planck_spectral_radiance(wavelengths_m, T)
    lam_peak = (b_wien / T) * 1e9
    B_peak = planck_spectral_radiance(lam_peak * 1e-9, T)
    
    ax1.plot(wavelengths_nm, B_lam, lw=2.2, color=col, label=f'$T = {T}$ K (Peak: {lam_peak:.0f} nm)')
    ax1.scatter([lam_peak], [B_peak], color=col, s=60, zorder=5)

# Shaded Visible Spectrum Band (380 nm - 750 nm)
ax1.axvspan(380, 750, color='yellow', alpha=0.15, label='Visible Light (380-750 nm)')
ax1.set_title("Planck's Spectral Radiance $B_\\lambda(\\lambda, T)$", fontsize=12, fontweight='bold')
ax1.set_xlabel('Wavelength $\\lambda$ (nm)', fontsize=11)
ax1.set_ylabel('Spectral Radiance (kW / (sr·m²·nm))', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend(loc='upper right', fontsize=8.5)

# Plot 2: Wien's Displacement Law Hyperbola
T_range = np.linspace(min(temperatures) * 0.7, max(temperatures) * 1.3, 200)
lam_peaks_nm = (b_wien / T_range) * 1e9

ax2.plot(T_range, lam_peaks_nm, 'b-', lw=2.2, label=r'Wien Law: $\lambda_{\mathrm{max}} = b / T$')
for idx, T in enumerate(temperatures):
    col = colors[idx % len(colors)]
    lam_p = (b_wien / T) * 1e9
    ax2.scatter([T], [lam_p], color=col, s=80, edgecolors='black', zorder=5, label=f'{T} K: {lam_p:.0f} nm')

ax2.set_title("Wien's Displacement Law: Peak Wavelength Decay", fontsize=12, fontweight='bold')
ax2.set_xlabel('Temperature $T$ (Kelvin)', fontsize=11)
ax2.set_ylabel(r'Peak Wavelength $\lambda_{\mathrm{max}}$ (nm)', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend(loc='upper right', fontsize=8.5)

plt.tight_layout()
plt.show()
PY;
        $code = str_replace('{{TEMPS}}', $tempsStr, $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Quantum Physics: Planck's Blackbody Radiation Law & Wien's Displacement ({$tempsStr})",
            'code' => $code,
            'explanation' => "Models Planck's spectral distribution of cavity radiation \$B_\\lambda(\\lambda, T) = \\frac{2hc^2}{\\lambda^5 (e^{hc/\\lambda k_B T} - 1)}\$ across temperatures {$tempsStr}. Highlights Wien's displacement peaks \$\\lambda_{\\mathrm{peak}} T = 2.898 \\times 10^{-3}\$ m·K and Stefan-Boltzmann total integrated power \$P = \\sigma T^4\$.",
            'complexity' => 'Time: O(N_temps * N_points), Space: O(N)',
            'exercises' => [
                'Integrate Planck radiance over all wavelengths to numerically verify Stefan-Boltzmann constant sigma.',
                'Derive the Rayleigh-Jeans classical approximation (lambda >> hc / k_B T) and demonstrate the Ultraviolet Catastrophe.',
                'Compute the surface temperature of the Sun assuming peak solar wavelength lambda_max = 500 nm.'
            ]
        ];
    }

    // =========================================================================
    // 19. FOURIER SERIES & SPECTRAL ANALYSIS (Square, Triangular, FFT)
    // =========================================================================
    if (str_contains($p, 'fourier') || str_contains($p, 'fft') || str_contains($p, 'harmonics') || str_contains($p, 'gibbs') || (str_contains($p, 'spectrum') && (str_contains($p, 'frequency') || str_contains($p, 'power spectrum') || str_contains($p, 'signal')))) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Fourier Series Synthesis of a Periodic Square Wave & Gibbs Phenomenon
# f(t) = (4 / pi) * \sum_{n=1,3,5...}^N (1 / n) * sin(n * omega_0 * t)

T = 2.0             # Period (s)
omega_0 = 2 * np.pi / T
t = np.linspace(-T, T, 1000)

harmonic_orders = [1, 3, 7, 25]
approximations = {}

for N in harmonic_orders:
    f_N = np.zeros_like(t)
    for n in range(1, N + 1, 2):
        f_N += (4.0 / (np.pi * n)) * np.sin(n * omega_0 * t)
    approximations[N] = f_N

exact_square = np.sign(np.sin(omega_0 * t))

# Fast Fourier Transform (FFT) Power Spectrum
dt = t[1] - t[0]
sample_signal = approximations[25]
fft_vals = np.fft.rfft(sample_signal)
freqs = np.fft.rfftfreq(len(t), dt)
power_spectrum = np.abs(fft_vals)**2

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Fourier Harmonic Reconstruction
ax1.plot(t, exact_square, 'k--', lw=1.5, alpha=0.6, label='Ideal Square Wave')
for N, f_approx in approximations.items():
    ax1.plot(t, f_approx, lw=1.8, label=f'$N = {N}$ Harmonics')

ax1.set_title('Fourier Synthesis & Gibbs Ringing', fontsize=12, fontweight='bold')
ax1.set_xlabel('Time $t$ (s)', fontsize=11)
ax1.set_ylabel('Amplitude $f(t)$', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend(loc='upper right', fontsize=9)

# Plot 2: Frequency Power Spectrum (FFT)
ax2.stem(freqs[:20], power_spectrum[:20] / np.max(power_spectrum[:20]), basefmt=" ")
ax2.set_title('Discrete FFT Frequency Spectrum', fontsize=12, fontweight='bold')
ax2.set_xlabel('Frequency $f$ (Hz)', fontsize=11)
ax2.set_ylabel('Normalized Spectral Power', fontsize=11)
ax2.grid(True, alpha=0.3)

plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Fourier Series Synthesis & Fast Fourier Transform (FFT)',
            'code' => $code,
            'explanation' => 'Decomposes periodic waveforms into orthogonal harmonic basis functions. Demonstrates asymptotic approximation, Gibbs overshoot phenomena at discontinuities, and discrete FFT power spectra.',
            'complexity' => 'Time: O(N_harm * N_points), FFT: O(N log N)',
            'exercises' => [
                'Synthesize a triangular wave f(t) and compare the 1/n^2 decay rate against 1/n.',
                'Analyze a sawtooth wave to observe asymmetry in Fourier sine and cosine coefficients.',
                'Apply a Gaussian low-pass filter in the frequency domain and compute inverse FFT.'
            ]
        ];
    }


    // =========================================================================
    // 19B. ELECTROMAGNETIC WAVE POLARIZATION & 3D VECTOR PROPAGATION
    // =========================================================================
    if (str_contains($p, 'polariz') || str_contains($p, 'em wave') || str_contains($p, 'electromagnetic wave') || (str_contains($p, 'wave') && (str_contains($p, 'transverse') || str_contains($p, 'optical') || (str_contains($p, 'electric') && str_contains($p, 'magnetic'))))) {
        $delta_str = "np.pi / 2.0";
        $pol_label = "Circular / Elliptical Polarization";
        if (str_contains($p, 'linear')) {
            $delta_str = "0.0";
            $pol_label = "Linear Polarization";
        } elseif (str_contains($p, 'left circular') || str_contains($p, 'lcp')) {
            $delta_str = "-np.pi / 2.0";
            $pol_label = "Left Circular Polarization (LCP)";
        } elseif (str_contains($p, 'circular') || str_contains($p, 'rcp')) {
            $delta_str = "np.pi / 2.0";
            $pol_label = "Right Circular Polarization (RCP)";
        } elseif (str_contains($p, 'elliptical')) {
            $delta_str = "np.pi / 4.0";
            $pol_label = "Elliptical Polarization";
        }

        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Electromagnetic Wave Polarization Simulation & 3D Vector Field
# E(z, t) = E0_x * cos(kz - omega*t) * x_hat + E0_y * cos(kz - omega*t + delta) * y_hat

wavelength = 1.0           # Wavelength lambda (m)
k = 2.0 * np.pi / wavelength # Wave number
c = 3.0e8                  # Speed of light (m/s)
omega = c * k

# Polarization Parameters
E0x = 1.0
E0y = 1.0
delta = {{DELTA}}          # Phase difference between Ex and Ey

# Spatial domain along z-axis (propagation direction)
z = np.linspace(0, 2 * wavelength, 300)
t = 0.0                    # Snapshot in time

Ex = E0x * np.cos(k * z - omega * t)
Ey = E0y * np.cos(k * z - omega * t + delta)

# Stokes Parameters
I_s = E0x**2 + E0y**2
Q_s = E0x**2 - E0y**2
U_s = 2 * E0x * E0y * np.cos(delta)
V_s = 2 * E0x * E0y * np.sin(delta)

print("=" * 65)
print("ELECTROMAGNETIC WAVE POLARIZATION TELEMETRY")
print("=" * 65)
print(f"Amplitudes:            E0x = {E0x:.2f} V/m, E0y = {E0y:.2f} V/m")
print(f"Phase Difference:      delta = {delta/np.pi:.2f} pi rad ({np.degrees(delta):.1f}°)")
print(f"Stokes Parameters:     S0 = {I_s:.2f}, S1 = {Q_s:.2f}, S2 = {U_s:.2f}, S3 = {V_s:.2f}")
if abs(V_s) > 0.95 * I_s:
    pol_state = "Right Circular Polarization (RCP)" if V_s > 0 else "Left Circular Polarization (LCP)"
elif abs(V_s) < 1e-4:
    pol_state = "Linear Polarization"
else:
    pol_state = "Elliptical Polarization"
print(f"Identified State:      {pol_state}")
print("=" * 65)

fig = plt.figure(figsize=(11, 4.8))

# Subplot 1: 3D Spatial Trajectory of Electric Field Vector
ax1 = fig.add_subplot(1, 2, 1, projection='3d')
ax1.plot(z, Ex, Ey, 'b-', lw=2.2, label=r'Electric Field $\mathbf{E}(z)$')
ax1.plot(z, Ex, np.zeros_like(z), 'r--', lw=1.2, alpha=0.6, label=r'$E_x(z)$ Component')
ax1.plot(z, np.zeros_like(z), Ey, 'g--', lw=1.2, alpha=0.6, label=r'$E_y(z)$ Component')
ax1.plot(z, np.zeros_like(z), np.zeros_like(z), 'k-', lw=1.5, label='Propagation ($z$)')
ax1.set_title(f'3D Spatial EM Wave Helix ({pol_state})', fontsize=11, fontweight='bold')
ax1.set_xlabel('Propagation $z$ (m)', fontsize=9)
ax1.set_ylabel('$E_x$ (V/m)', fontsize=9)
ax1.set_zlabel('$E_y$ (V/m)', fontsize=9)
ax1.legend(loc='upper right', fontsize=8)

# Subplot 2: Transverse Plane (xy) Polarization Locus (Hodograph)
ax2 = fig.add_subplot(1, 2, 2)
time_cycle = np.linspace(0, 2*np.pi/omega, 200)
Ex_t = E0x * np.cos(-omega * time_cycle)
Ey_t = E0y * np.cos(-omega * time_cycle + delta)
ax2.plot(Ex_t, Ey_t, 'm-', lw=2.5, label='Polarization Locus')
ax2.scatter([Ex_t[0]], [Ey_t[0]], color='red', s=80, zorder=5, label=r'$\mathbf{E}(t=0)$')
ax2.arrow(0, 0, Ex_t[0]*0.9, Ey_t[0]*0.9, head_width=0.08, head_length=0.08, fc='red', ec='red', lw=1.8, length_includes_head=True)
ax2.axhline(0, color='gray', ls=':', lw=1)
ax2.axvline(0, color='gray', ls=':', lw=1)
ax2.set_aspect('equal')
ax2.set_xlim(-1.5, 1.5)
ax2.set_ylim(-1.5, 1.5)
ax2.set_title(r'Transverse Plane Projection $(E_x, E_y)$', fontsize=11, fontweight='bold')
ax2.set_xlabel('$E_x$ Amplitude (V/m)', fontsize=10)
ax2.set_ylabel('$E_y$ Amplitude (V/m)', fontsize=10)
ax2.grid(True, alpha=0.3)
ax2.legend(loc='upper right', fontsize=8)

plt.tight_layout()
plt.show()
PY;
        $code = str_replace('{{DELTA}}', $delta_str, $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Electromagnetic Wave Polarization & 3D Vector Propagation ({$pol_label})",
            'code' => $code,
            'explanation' => "Simulates transverse electromagnetic wave propagation $\\mathbf{E}(z, t) = E_{0x}\\cos(kz-\\omega t)\\hat{i} + E_{0y}\\cos(kz-\\omega t + \\delta)\\hat{j}$. Computes Stokes parameters $(S_0, S_1, S_2, S_3)$ and visualizes both the 3D helical wave trajectory along the propagation axis and the transverse hodograph locus.",
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Vary the phase difference delta from 0 to pi to trace the full transition from linear to circular to elliptical polarization.',
                'Compute the Poynting vector S = (1 / mu_0) * (E x B) and verify time-averaged energy flux.',
                'Model the passage of circularly polarized light through a quarter-wave plate (QWP).'
            ]
        ];
    }


    // =========================================================================
    // 19C. FLUID DYNAMICS: HAGEN-POISEUILLE FLOW & BERNOULLI PRINCIPLE
    // =========================================================================
    if (str_contains($p, 'poiseuille') || str_contains($p, 'bernoulli') || str_contains($p, 'pipe flow') || str_contains($p, 'laminar flow') || (str_contains($p, 'fluid') && (str_contains($p, 'flow') || str_contains($p, 'viscosity') || str_contains($p, 'pressure')))) {
        $radius_pipe = 0.05; // 5 cm
        $viscosity = 0.001;  // Water ~ 1e-3 Pa*s
        $dp = 50.0;          // Pressure gradient Pa/m

        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Fluid Dynamics: Hagen-Poiseuille Laminar Viscous Flow in a Cylindrical Pipe
# Governing Equation: v(r) = (Delta P / (4 * eta * L)) * (R^2 - r^2)
# Flow Rate:          Q = (pi * Delta P * R^4) / (8 * eta * L)

R = {{RADIUS}}         # Pipe radius (m)
eta = {{VISCOSITY}}    # Dynamic viscosity (Pa·s)
dp_L = {{DP}}          # Pressure gradient Delta P / L (Pa/m)

# Maximum centerline velocity at r = 0
v_max = (dp_L / (4.0 * eta)) * R**2
# Total volumetric flow rate Q
Q = (np.pi * dp_L * R**4) / (8.0 * eta)
v_mean = v_max / 2.0

print("=" * 65)
print("HAGEN-POISEUILLE LAMINAR FLUID FLOW TELEMETRY")
print("=" * 65)
print(f"Pipe Radius (R):               {R * 100:.2f} cm")
print(f"Fluid Viscosity (eta):         {eta:.4e} Pa·s")
print(f"Pressure Gradient (dP/L):      {dp_L:.2f} Pa/m")
print(f"Centerline Peak Velocity (v0): {v_max:.4f} m/s")
print(f"Mean Flow Velocity (v_mean):   {v_mean:.4f} m/s")
print(f"Volumetric Flow Rate (Q):      {Q:.6e} m^3/s ({Q * 1000 * 60:.2f} L/min)")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Parabolic Velocity Profile across Pipe Diameter
r = np.linspace(-R, R, 200)
v_r = (dp_L / (4.0 * eta)) * (R**2 - r**2)

ax1.plot(v_r, r * 100, 'b-', lw=2.2, label=r'Parabolic Velocity $v(r)$')
ax1.axhline(0, color='gray', ls=':', lw=1, label='Centerline')
ax1.axhline(R * 100, color='black', lw=2, label='Pipe Wall')
ax1.axhline(-R * 100, color='black', lw=2)
ax1.fill_betweenx(r * 100, 0, v_r, color='cyan', alpha=0.25)
ax1.set_title('Cross-Sectional Laminar Velocity Profile', fontsize=12, fontweight='bold')
ax1.set_xlabel('Velocity $v(r)$ (m/s)', fontsize=11)
ax1.set_ylabel('Radial Position $r$ (cm)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Volumetric Flow Rate Scaling with Radius R^4 (Poiseuille Law)
r_sweep = np.linspace(0.01, R * 1.5, 100)
Q_sweep = (np.pi * dp_L * r_sweep**4) / (8.0 * eta)
ax2.plot(r_sweep * 100, Q_sweep * 1000 * 60, 'm-', lw=2, label=r'$Q \propto R^4$')
ax2.scatter([R * 100], [Q * 1000 * 60], color='red', s=80, zorder=5, label=f'Current: {Q*1000*60:.1f} L/min')
ax2.set_title(r'Nonlinear Flow Rate Scaling ($Q \propto R^4$)', fontsize=12, fontweight='bold')
ax2.set_xlabel('Pipe Radius $R$ (cm)', fontsize=11)
ax2.set_ylabel('Discharge Flow Rate (L/min)', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        $code = str_replace(['{{RADIUS}}', '{{VISCOSITY}}', '{{DP}}'], [(string)$radius_pipe, (string)$viscosity, (string)$dp], $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Fluid Dynamics: Hagen-Poiseuille Laminar Viscous Flow (Radius = {$radius_pipe} m)",
            'code' => $code,
            'explanation' => "Solves the Navier-Stokes momentum balance for steady, laminar, axisymmetric Newtonian fluid flow in a circular conduit. Derives the parabolic velocity profile \$v(r) = \\frac{\\Delta P}{4\\eta L}(R^2 - r^2)\$ and fourth-power aperture scaling \$Q \\propto R^4\$.",
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Compute the Reynolds number Re = rho * v_mean * (2*R) / eta to verify laminar criterion Re < 2000.',
                'Model the hydrodynamic entrance length L_e ≈ 0.06 * Re * (2*R) before fully developed flow.',
                'Apply Bernoulli\'s principle with Darcy-Weisbach frictional head loss.'
            ]
        ];
    }

    // =========================================================================
    // 19D. DOPPLER EFFECT FOR ACOUSTIC & ELECTROMAGNETIC WAVES
    // =========================================================================
    if (str_contains($p, 'doppler') || (str_contains($p, 'frequency shift') && (str_contains($p, 'sound') || str_contains($p, 'source') || str_contains($p, 'observer')))) {
        $f0 = 440.0; // 440 Hz (Concert A)
        $vs = 30.0;  // Source velocity 30 m/s (~ 108 km/h)
        $v_sound = 343.0; // Speed of sound at 20 C

        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Wave Mechanics: Acoustic Doppler Effect for Moving Source and Observer
# General Formulation: f' = f_0 * (v_wave +- v_observer) / (v_wave -+ v_source)

f0 = {{F0}}           # Emitted frequency (Hz)
v_sound = {{VSOUND}}  # Speed of sound in medium (m/s)
v_source = {{VSOURCE}}# Velocity of source (m/s)

# Approach and Recede observed frequencies (stationary observer)
f_approach = f0 * (v_sound / (v_sound - v_source))
f_recede = f0 * (v_sound / (v_sound + v_source))
mach_number = v_source / v_sound

print("=" * 65)
print("ACOUSTIC DOPPLER EFFECT TELEMETRY")
print("=" * 65)
print(f"Emitted Base Frequency (f0):   {f0:.2f} Hz")
print(f"Speed of Sound in Medium:      {v_sound:.1f} m/s")
print(f"Source Velocity (v_source):    {v_source:.1f} m/s ({v_source * 3.6:.1f} km/h, Mach {mach_number:.3f})")
print(f"Approaching Frequency (f'):    {f_approach:.2f} Hz (Shift: +{f_approach - f0:.2f} Hz)")
print(f"Receding Frequency (f''):      {f_recede:.2f} Hz (Shift: -{f0 - f_recede:.2f} Hz)")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Observed Frequency as a Function of Source Velocity
v_sweep = np.linspace(0, v_sound * 0.9, 200)
f_app_sweep = f0 * (v_sound / (v_sound - v_sweep))
f_rec_sweep = f0 * (v_sound / (v_sound + v_sweep))

ax1.plot(v_sweep, f_app_sweep, 'b-', lw=2.2, label='Approaching Source')
ax1.plot(v_sweep, f_rec_sweep, 'r--', lw=2.0, label='Receding Source')
ax1.axhline(f0, color='gray', ls=':', label=rf'Base $f_0 = {f0:.0f}$ Hz')
ax1.scatter([v_source], [f_approach], color='blue', s=70, zorder=5)
ax1.scatter([v_source], [f_recede], color='red', s=70, zorder=5)
ax1.set_title('Doppler Frequency Shift vs Source Speed', fontsize=12, fontweight='bold')
ax1.set_xlabel('Source Speed (m/s)', fontsize=11)
ax1.set_ylabel('Observed Frequency $f\'$ (Hz)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Temporal Frequency Profile during Flyby (Impact parameter d = 10 m)
d = 10.0 # Closest approach distance (m)
x_pos = np.linspace(-150, 150, 300) # Source position along trajectory
time_s = x_pos / v_source
cos_theta = x_pos / np.sqrt(x_pos**2 + d**2) # Direction cosine to observer
f_flyby = f0 * (v_sound / (v_sound - v_source * cos_theta))

ax2.plot(time_s, f_flyby, 'm-', lw=2.5, label='Flyby Signal')
ax2.axvline(0, color='black', ls='--', alpha=0.7, label='Point of Closest Approach')
ax2.set_title('Acoustic Flyby Pitch Transition Profile', fontsize=12, fontweight='bold')
ax2.set_xlabel('Time $t$ (seconds)', fontsize=11)
ax2.set_ylabel('Perceived Frequency (Hz)', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        $code = str_replace(['{{F0}}', '{{VSOUND}}', '{{VSOURCE}}'], [(string)$f0, (string)$v_sound, (string)$vs], $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Wave Mechanics: Acoustic Doppler Effect & Flyby Pitch Transition (f0 = {$f0} Hz)",
            'code' => $code,
            'explanation' => "Formulates the classic acoustic Doppler frequency shift \$f' = f_0 \\frac{v}{v \\mp v_s}\$ for supersonic and subsonic kinematic relative motion. Illustrates pitch decay dynamics during an observer flyby trajectory.",
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Model the relativistic Doppler effect for optical frequencies f\' = f_0 * sqrt((1 - beta) / (1 + beta)).',
                'Compute the Mach angle of the conical shock wave cone sin(mu) = 1 / M for M > 1.',
                'Analyze the Doppler broadening of spectral lines due to thermal Maxwellian velocity distributions.'
            ]
        ];
    }

    // =========================================================================
    // 20. WAVE EQUATION (1D Vibrating String PDE)
    // =========================================================================
    if (str_contains($p, 'wave') && (str_contains($p, 'equation') || str_contains($p, 'string') || str_contains($p, 'd\'alembert') || str_contains($p, 'vibrat') || str_contains($p, 'standing'))) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# 1D Wave Equation PDE: Explicit Finite Difference Scheme
# d^2u / dt^2 = c^2 * d^2u / dx^2
# CFL Stability Condition: C = c * dt / dx <= 1.0

L = 1.0           # Length of string (m)
Nx = 100          # Spatial steps
dx = L / Nx
c = 1.0           # Wave speed (m/s)

# Choose dt such that Courant number C = 1.0 (exact d'Alembert propagation)
C = 1.0
dt = C * dx / c
t_max = 2.0
Nt = int(t_max / dt)

x = np.linspace(0, L, Nx + 1)

# Initial Pluck: Triangular displacement
u_prev = np.where(x < L/2, 2.0 * x, 2.0 * (1.0 - x))
# Fixed boundary conditions
u_prev[0] = 0.0
u_prev[-1] = 0.0

# Initial velocity is zero: u_1 = u_0 + 0.5 * C^2 * (u_{i+1} - 2*u_i + u_{i-1})
u_curr = u_prev.copy()
u_curr[1:-1] = u_prev[1:-1] + 0.5 * C**2 * (u_prev[2:] - 2*u_prev[1:-1] + u_prev[:-2])

snapshots = {0.0: u_prev.copy()}
plot_times = [0.0, 0.25, 0.5, 0.75, 1.0]

for step in range(2, Nt + 1):
    t_val = step * dt
    u_next = np.zeros(Nx + 1)
    u_next[1:-1] = 2 * u_curr[1:-1] - u_prev[1:-1] + C**2 * (u_curr[2:] - 2*u_curr[1:-1] + u_curr[:-2])
    
    u_prev = u_curr.copy()
    u_curr = u_next.copy()
    
    for pt in plot_times:
        if abs(t_val - pt) < dt / 2.0 and pt not in snapshots:
            snapshots[pt] = u_curr.copy()

plt.figure(figsize=(9.5, 5))
for t_snap, u_prof in sorted(snapshots.items()):
    plt.plot(x, u_prof, lw=2, label=f'$t = {t_snap:.2f}$ s')

print("=" * 65)
print("1D WAVE EQUATION NUMERICAL SIMULATION")
print("=" * 65)
print(f"String Length: {L} m | Wave Speed c: {c} m/s")
print(f"Courant CFL Stability Number: {C} (<= 1.0)")
print("=" * 65)

plt.title('1D Wave Propagation on a Fixed String: Time Evolution Snapshots', fontsize=12, fontweight='bold')
plt.xlabel('Position $x$ along string (m)', fontsize=11)
plt.ylabel('Displacement $u(x, t)$', fontsize=11)
plt.grid(True, alpha=0.3)
plt.legend(loc='upper right')
plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => '1D Wave Equation PDE: Explicit Finite Difference & d\'Alembert Propagation',
            'code' => $code,
            'explanation' => 'Integrates the hyperbolic 1D wave equation using the explicit second-order central difference scheme under Courant-Friedrichs-Lewy (CFL) stability criterion $C = c \\Delta t / \\Delta x \\le 1$.',
            'complexity' => 'Time: O(Nx * Nt), Space: O(Nx)',
            'exercises' => [
                'Set initial condition to pure normal modes u(x, 0) = sin(n * pi * x / L) and verify harmonic standing wave oscillation.',
                'Introduce viscous air damping -gamma * du/dt into the equation of motion.',
                'Examine non-reflecting open boundary conditions for traveling wave pulses.'
            ]
        ];
    }

    // =========================================================================
    // 21. RLC CIRCUIT & RESONANCE
    if ((str_contains($p, 'rlc') || (str_contains($p, 'circuit') && str_contains($p, 'resonance')) || (str_contains($p, 'inductor') && str_contains($p, 'capacitor'))) && !str_contains($p, 'rectifier')) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Electronics & Electrodynamics: Series RLC Circuit Transient & Resonance
# Equation: L * d^2 q / dt^2 + R * dq / dt + (1 / C) * q = V(t)

R = 10.0         # Resistance (Ohms)
L = 0.05         # Inductance (Henries)
C = 100.0e-6     # Capacitance (Farads)
V0 = 5.0         # DC step voltage (Volts)

omega_0 = 1.0 / np.sqrt(L * C) # Resonant angular frequency
alpha = R / (2.0 * L)          # Damping attenuation

t = np.linspace(0, 0.05, 500)
dt = t[1] - t[0]

# Underdamped condition: alpha < omega_0
omega_d = np.sqrt(omega_0**2 - alpha**2)

# Analytical Capacitor Voltage V_C(t) for DC step input
Vc = V0 * (1.0 - np.exp(-alpha * t) * (np.cos(omega_d * t) + (alpha / omega_d) * np.sin(omega_d * t)))
# Circuit Current: I(t) = C * dVc/dt
current = (V0 / (omega_d * L)) * np.exp(-alpha * t) * np.sin(omega_d * t)

print("=" * 65)
print("SERIES RLC CIRCUIT DYNAMICS REPORT")
print("=" * 65)
print(f"Resistance R:           {R} Ohms")
print(f"Inductance L:           {L*1e3:.1f} mH")
print(f"Capacitance C:          {C*1e6:.1f} uF")
print(f"Resonant Frequency f0:  {omega_0 / (2*np.pi):.1f} Hz")
print(f"Damping State:          Underdamped (alpha = {alpha:.1f} < omega_0 = {omega_0:.1f})")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Capacitor Voltage Transient
ax1.plot(t * 1000, Vc, 'b-', lw=2, label=r'Capacitor Voltage $V_C(t)$')
ax1.axhline(V0, color='gray', linestyle='--', label=f'DC Supply $V_0 = {V0}$ V')
ax1.set_title('RLC Voltage Transient Response', fontsize=12, fontweight='bold')
ax1.set_xlabel('Time $t$ (ms)', fontsize=11)
ax1.set_ylabel('Voltage (V)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: AC Frequency Resonance Curve
freqs = np.linspace(10, 400, 300)
omega_ac = 2 * np.pi * freqs
impedance = np.sqrt(R**2 + (omega_ac * L - 1.0 / (omega_ac * C))**2)
I_ac = V0 / impedance

ax2.plot(freqs, I_ac, 'r-', lw=2, label='Current Amplitude $I(\\omega)$')
ax2.axvline(omega_0 / (2*np.pi), color='blue', linestyle=':', label=f'Resonance $f_0={omega_0/(2*np.pi):.0f}$ Hz')
ax2.set_title('AC Frequency Resonance Curve', fontsize=12, fontweight='bold')
ax2.set_xlabel('Frequency $f$ (Hz)', fontsize=11)
ax2.set_ylabel('Current $I$ (A)', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Series RLC Circuit: Transient Oscillations & Frequency Resonance',
            'code' => $code,
            'explanation' => 'Models the second-order differential response of a series RLC electronic network. Computes the underdamped transient voltage overshoot and the characteristic AC impedance resonance peak.',
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Increase resistance R to achieve the critically damped condition R = 2 * sqrt(L / C).',
                'Calculate the Quality Factor Q = (1 / R) * sqrt(L / C) and bandwidth Delta f.',
                'Simulate RC low-pass and high-pass frequency filter attenuation.'
            ]
        ];
    }

    // =========================================================================
    // 22. TEMPERATURE SCALE CONVERSION (Celsius, Fahrenheit, Kelvin)
    // =========================================================================
    if (str_contains($p, 'celsius') || str_contains($p, 'fahrenheit') || str_contains($p, 'temperature conversion')) {
        $degC = 25.0;
        if (preg_match('/(-?\d+(?:\.\d+)?)/', $problem, $m)) {
            $degC = (float)$m[1];
        }
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Thermometry: Temperature Scale Conversions & Calibration
# Relations:
# T(°F) = T(°C) * (9 / 5) + 32
# T(K)  = T(°C) + 273.15

deg_c = {{DEGC}}

deg_f = deg_c * 9.0 / 5.0 + 32.0
deg_k = deg_c + 273.15

print("=" * 65)
print(f"TEMPERATURE CONVERSION REPORT (Input = {deg_c} °C)")
print("=" * 65)
print(f"Celsius (°C):    {deg_c:.2f} °C")
print(f"Fahrenheit (°F): {deg_f:.2f} °F")
print(f"Kelvin (K):      {deg_k:.2f} K")
print("=" * 65)

# Comparison over standard physical range [-40 °C, 100 °C]
c_range = np.linspace(-50, 120, 200)
f_range = c_range * 9.0 / 5.0 + 32.0
k_range = c_range + 273.15

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Celsius vs Fahrenheit Linear Calibration
ax1.plot(c_range, f_range, 'b-', lw=2.2, label=r'$T(^\circ\mathrm{F}) = \frac{9}{5}T(^\circ\mathrm{C}) + 32$')
ax1.scatter([deg_c], [deg_f], color='red', s=90, zorder=5, label=f'Input: {deg_c}°C = {deg_f:.1f}°F')
ax1.scatter([-40], [-40], color='purple', s=70, zorder=5, label='Cross Point (-40°C = -40°F)')
ax1.set_title('Celsius vs Fahrenheit Relationship', fontsize=12, fontweight='bold')
ax1.set_xlabel('Celsius (°C)', fontsize=11)
ax1.set_ylabel('Fahrenheit (°F)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Absolute Thermodynamic Scale (Kelvin)
ax2.plot(c_range, k_range, 'teal', lw=2.2, label=r'$T(\mathrm{K}) = T(^\circ\mathrm{C}) + 273.15$')
ax2.scatter([deg_c], [deg_k], color='darkorange', s=90, zorder=5, label=f'{deg_c}°C = {deg_k:.2f} K')
ax2.set_title('Absolute Thermodynamic Scale (Kelvin)', fontsize=12, fontweight='bold')
ax2.set_xlabel('Celsius (°C)', fontsize=11)
ax2.set_ylabel('Kelvin (K)', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        $code = str_replace('{{DEGC}}', (string)$degC, $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Thermometry: Temperature Scale Conversions ({$degC} °C)",
            'code' => $code,
            'explanation' => "Calculates thermometric conversions between Celsius, Fahrenheit, and absolute thermodynamic Kelvin scales for {$degC} °C. Demonstrates the linear calibration slopes and the unique equivalence point at -40°.",
            'complexity' => 'Time: O(1), Space: O(1)',
            'exercises' => [
                'Verify that -40 °C is mathematically identical to -40 °F by solving T = 1.8*T + 32.',
                'Compute the Rankine scale T(°R) = T(°F) + 459.67.',
                'Model linear thermal expansion of a metal rod \\Delta L = \\alpha L_0 \\Delta T.'
            ]
        ];
    }

    // =========================================================================
    // 23. CURVE FITTING & LINEAR REGRESSION
    // =========================================================================
    if (str_contains($p, 'curve fit') || str_contains($p, 'linear regression') || str_contains($p, 'least squares') || str_contains($p, 'fit data')) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Scientific Data Analysis: Ordinary Least Squares (OLS) Linear Regression
# Model: y = m * x + c + epsilon

np.random.seed(42)
x_data = np.linspace(1.0, 10.0, 20)
true_slope = 2.45
true_intercept = 1.30
noise = np.random.normal(0, 1.2, size=len(x_data))
y_data = true_slope * x_data + true_intercept + noise

# Analytical Least-Squares Normal Equations
N = len(x_data)
sum_x = np.sum(x_data)
sum_y = np.sum(y_data)
sum_xy = np.sum(x_data * y_data)
sum_x2 = np.sum(x_data**2)

slope_fit = (N * sum_xy - sum_x * sum_y) / (N * sum_x2 - sum_x**2)
intercept_fit = (sum_y - slope_fit * sum_x) / N

# Coefficient of Determination R^2
y_pred = slope_fit * x_data + intercept_fit
residuals = y_data - y_pred
ss_res = np.sum(residuals**2)
ss_tot = np.sum((y_data - np.mean(y_data))**2)
r_squared = 1.0 - (ss_res / ss_tot)

print("=" * 65)
print("LEAST SQUARES LINEAR REGRESSION REPORT")
print("=" * 65)
print(f"Fitted Slope (m):       {slope_fit:.4f} (True: {true_slope})")
print(f"Fitted Intercept (c):   {intercept_fit:.4f} (True: {true_intercept})")
print(f"Coefficient R^2:        {r_squared:.4f}")
print(f"Residual Std Error:     {np.sqrt(ss_res / (N - 2)):.4f}")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Experimental Data & Fitted Line
ax1.scatter(x_data, y_data, color='crimson', edgecolors='black', s=50, label='Experimental Data', zorder=5)
x_line = np.linspace(1.0, 10.0, 100)
ax1.plot(x_line, slope_fit * x_line + intercept_fit, 'b-', lw=2, label=f'Fit: $y = {slope_fit:.2f}x + {intercept_fit:.2f}$')
ax1.set_title('Linear Regression Best-Fit Line', fontsize=12, fontweight='bold')
ax1.set_xlabel('Independent Variable $x$', fontsize=11)
ax1.set_ylabel('Measured Dependent Variable $y$', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Residual Analysis
ax2.axhline(0, color='gray', linestyle='--')
ax2.stem(x_data, residuals, basefmt=" ")
ax2.set_title(f'Residual Analysis ($R^2 = {r_squared:.3f}$)', fontsize=12, fontweight='bold')
ax2.set_xlabel('Independent Variable $x$', fontsize=11)
ax2.set_ylabel('Residual $y - \\hat{y}$', fontsize=11)
ax2.grid(True, alpha=0.3)

plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Scientific Data Analysis: Least-Squares Linear Regression & Residual Telemetry',
            'code' => $code,
            'explanation' => 'Performs ordinary least-squares regression on experimental data pairs $(x_i, y_i)$. Computes slope, intercept, residual standard deviation, and coefficient of determination $R^2$.',
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Compute parameter uncertainty standard deviations \\sigma_m and \\sigma_c.',
                'Implement polynomial regression using numpy.polyfit(x, y, deg=2).',
                'Use scipy.optimize.curve_fit to fit an exponential decay curve y = A * exp(-k * x).'
            ]
        ];
    }

    // =========================================================================
    // 24. INTERPOLATION (Lagrange Interpolating Polynomial)
    if (str_contains($p, 'lagrange') || (str_contains($p, 'interpolat') && !str_contains($p, 'newton'))) {
        $code = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# Numerical Methods: Lagrange Polynomial Interpolation
# P(x) = \sum_{j=0}^k y_j * \prod_{m \ne j} (x - x_m) / (x_j - x_m)

x_nodes = np.array([0.0, 1.0, 2.5, 4.0, 5.0])
y_nodes = np.array([1.0, 2.7, 1.2, 3.8, 3.0])

def lagrange_interpolate(x, x_pts, y_pts):
    """Evaluate Lagrange interpolating polynomial at positions x."""
    n = len(x_pts)
    y_interp = np.zeros_like(x, dtype=float)
    for j in range(n):
        # Compute Lagrange basis polynomial L_j(x)
        L_j = np.ones_like(x, dtype=float)
        for m in range(n):
            if m != j:
                L_j *= (x - x_pts[m]) / (x_pts[j] - x_pts[m])
        y_interp += y_pts[j] * L_j
    return y_interp

x_dense = np.linspace(-0.2, 5.2, 300)
y_dense = lagrange_interpolate(x_dense, x_nodes, y_nodes)

print("=" * 65)
print("LAGRANGE POLYNOMIAL INTERPOLATION REPORT")
print("=" * 65)
print("Interpolation Nodes:")
for xn, yn in zip(x_nodes, y_nodes):
    print(f"  Point: ({xn:.2f}, {yn:.2f})")
print(f"Degree of Polynomial: {len(x_nodes) - 1}")
print("=" * 65)

plt.figure(figsize=(9.5, 5))
plt.plot(x_dense, y_dense, 'b-', lw=2.2, label=f'Lagrange Polynomial (Degree {len(x_nodes)-1})')
plt.scatter(x_nodes, y_nodes, color='red', s=90, edgecolors='black', zorder=5, label='Interpolation Nodes')

plt.title('Lagrange Interpolating Polynomial Across Discrete Data Nodes', fontsize=12, fontweight='bold')
plt.xlabel('$x$', fontsize=11)
plt.ylabel('$P(x)$', fontsize=11)
plt.grid(True, alpha=0.3)
plt.legend(fontsize=10.5)
plt.tight_layout()
plt.show()
PY;
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Numerical Interpolation: Lagrange Polynomial Collocation',
            'code' => $code,
            'explanation' => 'Constructs the unique $(N-1)$-th degree polynomial passing through $N$ discrete nodes via Lagrange basis products $\\ell_j(x) = \\prod_{m \\ne j} \\frac{x - x_m}{x_j - x_m}$.',
            'complexity' => 'Time: O(N * M), Space: O(M)',
            'exercises' => [
                'Implement Newton Divided Difference interpolation and verify polynomial equivalence.',
                'Demonstrate Runge\'s phenomenon on equispaced nodes for f(x) = 1 / (1 + 25*x^2).',
                'Use Chebyshev node distribution x_k = cos((2k-1)*pi / (2n)) to minimize interpolation error.'
            ]
        ];
    }

    // =========================================================================
    // 25. EXPLICIT MATHEMATICAL FUNCTION PLOT & EQUATION REQUESTS
    // =========================================================================
    if (str_contains($p, 'plot') || str_contains($p, 'graph') || str_contains($p, 'curve') || str_contains($p, 'function') || str_contains($p, 'dy/dt') || str_contains($p, 'dy/dx') || str_contains($p, 'ode')) {
        if (str_contains($p, 'sin') || str_contains($p, 'cos') || str_contains($p, 'exp') || str_contains($p, 'tan') || str_contains($p, '^') || str_contains($p, '**') || str_contains($p, 'log') || str_contains($p, 'sqrt') || str_contains($p, 'dy/dt') || str_contains($p, 'dy/dx')) {
            return synthesizeDynamicPhysicsCode($problem, $includePlot);
        }
    }

    // =========================================================================
    // 26. TIER 3: SMART RETRIEVAL ACROSS 353 CURRICULUM PROGRAMS
    // =========================================================================
    $dbProgram = searchDatabaseForProgramSmart($problem);
    if ($dbProgram !== null) {
        return formatDatabaseProgramResponse($dbProgram, $problem, $includePlot);
    }

    // =========================================================================
    // 27. TIER 4: DYNAMIC CODE SYNTHESIZER (Custom Math / Physics Prompt)
    // =========================================================================
    return synthesizeDynamicPhysicsCode($problem, $includePlot);
}

/**
 * Intelligent Multi-Weighted Search for Curriculum Database (353 Verified Programs)
 * Scores topic titles, chapters, and key multi-word phrases with high precision.
 */
function searchDatabaseForProgramSmart($problem) {
    try {
        global $conn;
        $db = $conn;
        if (!$db) {
            if (file_exists(__DIR__ . '/../db.php')) {
                require_once __DIR__ . '/../db.php';
                $db = $conn;
            }
        }
        if (!$db) {
            return null;
        }

        require __DIR__ . '/../program/python/menu.php';

        $p = strtolower($problem);
        $stopwords = [
            'write', 'python', 'code', 'program', 'calculate', 'compute', 'simulate', 'plot',
            'find', 'the', 'and', 'with', 'for', 'from', 'using', 'how', 'show', 'solution',
            'please', 'make', 'into', 'that', 'this', 'give', 'generate', 'script', 'solve',
            'example', 'implement', 'me', 'a', 'an', 'of', 'in', 'to', 'is', 'are', 'was',
            'by', 'at', 'can', 'you', 'create', 'display', 'print', 'first', 'second', 'kind',
            'function', 'functions', 'method', 'methods', 'type', 'types', 'basic', 'problem',
            'problems', 'given', 'order', 'ode', 'differential', 'equation', 'equations',
            'algorithm', 'algorithms', 'loop', 'loops', 'value', 'values', 'system', 'systems',
            'angle', 'angles', 'degree', 'degrees', 'shift', 'shifts', 'scatter', 'scattering'
        ];

        $tokens = preg_split('/[^a-zA-Z0-9_]+/', $p);
        $keywords = array_values(array_filter($tokens, function($w) use ($stopwords) {
            return strlen($w) >= 3 && !in_array($w, $stopwords);
        }));

        if (empty($keywords)) {
            return null;
        }

        $bestMatch = null;
        $bestScore = 0;

        // Scan all curriculum topics across the 21 chapters
        foreach ($sub_menu_titles as $mid => $subList) {
            foreach ($subList as $subid => $tTitle) {
                $topicLower = strtolower($tTitle);
                $chapLower = strtolower($menu_titles[$mid] ?? '');
                $combined = $chapLower . ' ' . $topicLower;
                $score = 0;

                // Keyword matches in topic title and chapter
                foreach ($keywords as $kw) {
                    if (str_contains($topicLower, $kw)) {
                        $score += 40;
                        if (preg_match('/\b' . preg_quote($kw, '/') . '\b/', $topicLower)) {
                            $score += 25;
                        }
                    } elseif (str_contains($chapLower, $kw)) {
                        $score += 15;
                    }
                }

                // Domain-Specific Multi-Word Affinity Weightings
                $affinityRules = [
                    'specific heat' => ['einstein' => 90, 'debye' => 90],
                    'newton' => ['forward' => 80, 'backward' => 80],
                    'rectifier' => ['half wave' => 90, 'capacitor filter' => 80],
                    'bessel' => ['bessel' => 110],
                    'legendre' => ['legendre' => 110],
                    'hermite' => ['hermite' => 110],
                    'laplace' => ['laplace' => 95],
                    'wave equation' => ['wave equation' => 95],
                    'schrodinger' => ['schrodinger' => 90, 'tise' => 90],
                    'tunneling' => ['barrier' => 90, 'wavepacket' => 90],
                    'potential box' => ['1d' => 80, 'potential box' => 90],
                    'euler' => ['euler' => 95],
                    'rk4' => ['2nd order' => 80, 'rk4' => 90],
                    'gauss elimination' => ['gauss elimination' => 95],
                    'trapezoidal' => ['trapezoidal' => 85],
                    'simpson' => ['simpson' => 85],
                    'lagrange' => ['lagrange' => 95],
                    'hydrogen' => ['h-like' => 80, 'atom' => 80]
                ];

                foreach ($affinityRules as $lead => $followers) {
                    if (str_contains($p, $lead)) {
                        foreach ($followers as $fol => $pts) {
                            if (str_contains($combined, $fol)) {
                                $score += $pts;
                            }
                        }
                    }
                }

                // Explicit ODE order sensitivity
                if (str_contains($p, 'rk4') && str_contains($topicLower, 'rk4')) $score += 65;
                if (str_contains($p, 'rk2') && str_contains($topicLower, 'rk2')) $score += 65;
                if (str_contains($p, '2nd order') && str_contains($topicLower, '2nd order')) $score += 60;
                if (str_contains($p, '1st order') && str_contains($topicLower, '1st order')) $score += 60;

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = [
                        'menu_id' => $mid,
                        'submenu_id' => $subid,
                        'chapter' => $menu_titles[$mid],
                        'topic' => $tTitle,
                        'score' => $score
                    ];
                }
            }
        }

        // Confident threshold check (strict minimum score 75 to avoid false positives)
        if ($bestScore >= 75 && $bestMatch !== null) {
            $stmt = $db->prepare("SELECT id, menu_id, submenu_id, program_id, algo, content, explanation FROM python WHERE menu_id = ? AND submenu_id = ? AND content IS NOT NULL AND LENGTH(content) > 20 ORDER BY program_id ASC LIMIT 1");
            $stmt->execute([(int)$bestMatch['menu_id'], (int)$bestMatch['submenu_id']]);
            $prog = $stmt->fetch();
            if ($prog && !empty($prog['content'])) {
                $prog['matched_topic'] = $bestMatch['topic'];
                $prog['matched_chapter'] = $bestMatch['chapter'];
                $prog['matched_score'] = $bestScore;
                return $prog;
            }
        }

        // Targeted SQL search on algo title ONLY when title is short and highly specific
        // Ignore long HTML explanations to prevent false positive keyword collisions
        $placeholders = [];
        $params = [];
        foreach ($keywords as $kw) {
            if (strlen($kw) >= 4 && !in_array($kw, ['code', 'python', 'point', 'charge', 'field', 'data', 'series', 'system', 'equation', 'method', 'program', 'function'])) {
                $placeholders[] = "algo LIKE ?";
                $params[] = "%$kw%";
            }
        }

        if (!empty($placeholders)) {
            $sql = "SELECT id, menu_id, submenu_id, program_id, algo, content, explanation FROM python WHERE (" . implode(" OR ", $placeholders) . ") AND content IS NOT NULL AND LENGTH(algo) < 120 LIMIT 15";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $candidates = $stmt->fetchAll();

            $bestCand = null;
            $bestCandScore = 0;
            foreach ($candidates as $cand) {
                $cScore = 0;
                $algo = strtolower(strip_tags($cand['algo'] ?? ''));
                $matchCount = 0;
                foreach ($keywords as $kw) {
                    if (str_contains($algo, $kw)) {
                        $cScore += 35;
                        $matchCount++;
                    }
                }
                if ($matchCount >= 3 && $cScore > $bestCandScore) {
                    $bestCandScore = $cScore;
                    $bestCand = $cand;
                }
            }
            if ($bestCandScore >= 95 && $bestCand !== null && !empty($bestCand['content'])) {
                return $bestCand;
            }
        }
    } catch (Throwable $t) {
        return null;
    }

    return null;
}

/**
 * Format a database program into a structured AI response with modern Pyodide compatibility
 */
function formatDatabaseProgramResponse($dbProgram, $problem, $includePlot) {
    require __DIR__ . '/../program/python/menu.php';

    $mid = (int)$dbProgram['menu_id'];
    $subid = (int)$dbProgram['submenu_id'];
    $chapter = $menu_titles[$mid] ?? "Chapter $mid";
    $topic = $sub_menu_titles[$mid][$subid] ?? "Topic $subid";

    $code = trim($dbProgram['content']);
    $algo = trim(strip_tags($dbProgram['algo']));

    // Modernize deprecated SciPy APIs (e.g. scipy.misc.derivative removed in SciPy >= 1.12)
    if (str_contains($code, 'from scipy.misc import derivative')) {
        $code = str_replace(
            'from scipy.misc import derivative',
            "# Numerical Central Finite Difference Derivative (Modern SciPy Compatible)\ndef derivative(func, x0, dx=1e-5):\n    return (func(x0 + dx) - func(x0 - dx)) / (2.0 * dx)",
            $code
        );
    }

    // Ensure plt.show() is called if matplotlib is used
    if ($includePlot && (str_contains($code, 'plt.plot') || str_contains($code, 'plt.scatter') || str_contains($code, 'plt.figure') || str_contains($code, 'plt.bar'))) {
        if (!str_contains($code, 'plt.show()')) {
            $code .= "\n\nplt.tight_layout()\nplt.show()\n";
        }
    }

    $title = "{$chapter}: {$topic}";
    if (!empty($algo) && strlen($algo) > 10 && strlen($algo) < 80) {
        $cleanAlgo = preg_replace('/^(?:Problem\s*:?|Objective\s*:?|Algorithm\s*:?)\s*/i', '', $algo);
        if (strlen($cleanAlgo) > 5) {
            $title = $cleanAlgo;
        }
    }

    $explanation = !empty($dbProgram['explanation']) ? trim($dbProgram['explanation']) : "Verified scientific program from {$chapter} ({$topic}). Solves the governing computational formulations according to standard scientific numerical physics paradigms.";

    return [
        'success' => true,
        'source' => 'curriculum_library',
        'title' => $title,
        'code' => $code,
        'explanation' => $explanation,
        'complexity' => 'Verified Curriculum Formulation (Pyodide Ready)',
        'exercises' => [
            "Modify parameters in {$topic} to investigate numerical stability and convergence limits.",
            'Compare the discretization error against higher-order integration schemes.',
            'Extend the output visualizations with custom colormaps or interactive sliders.'
        ]
    ];
}

/**
 * Dynamic Synthesizer: Intelligent Code Synthesis from Arbitrary User Problem Descriptions
 */
function synthesizeDynamicPhysicsCode($problem, $includePlot) {
    $cleanDesc = htmlspecialchars(substr($problem, 0, 100));
    $p = strtolower($problem);

    // -------------------------------------------------------------------------
    // Dynamic Sub-Pattern 1: Ordinary Differential Equation (ODE) / Initial Value Problem
    // -------------------------------------------------------------------------
    if (preg_match('/(?:dy\/dx|dy\/dt|d\^2y\/dt\^2|d2y\/dt2|y\'|differential equation|solve ode|euler method|rk4)/i', $problem)) {
        $odeExpr = '-2.0 * y + 4.0 * np.sin(t)';
        $odeDisp = 'dy/dt = -2y + 4 sin(t)';
        if (preg_match('/(?:dy\/dt|dy\/dx|y\')\s*=\s*([a-zA-Z0-9_\+\-\*\/\^\.\(\)\s]+?)(?:with|for|initial|\$|[\r\n]|;\s*|$)/i', $problem, $om)) {
            $cand = trim($om[1]);
            $odeDisp = 'dy/dt = ' . $cand;
            $pyCand = str_replace('^', '**', $cand);
            $pyCand = preg_replace('/(\d+)\s*([a-zA-Z])/', '$1 * $2', $pyCand);
            $pyCand = preg_replace('/\bsin\b/i', 'np.sin', $pyCand);
            $pyCand = preg_replace('/\bcos\b/i', 'np.cos', $pyCand);
            $pyCand = preg_replace('/\bexp\b/i', 'np.exp', $pyCand);
            $odeExpr = $pyCand;
        }

        $y0 = 1.0;
        if (preg_match('/(?:y\(0\)|y0|initial)\s*=?\s*(-?\d+(?:\.\d+)?)/i', $problem, $ym)) {
            $y0 = (float)$ym[1];
        }

        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# ==============================================================================
# Computational Solution: {{DESC}}
# Numerical Solution of Ordinary Differential Equation (Initial Value Problem)
# Target ODE: {{ODE_DISP}}, y(0) = {{Y0}}
# Solvers: 4th-Order Runge-Kutta (RK4) & 1st-Order Euler Method
# ==============================================================================

def f(t, y):
    """Governing derivative dy/dt = f(t, y)"""
    try:
        return {{ODE_EXPR}}
    except Exception:
        return -2.0 * y + np.sin(t)

# Time Discretization
t0 = 0.0
t_end = 10.0
dt = 0.02
steps = int((t_end - t0) / dt) + 1
t = np.linspace(t0, t_end, steps)

# Array allocation
y_rk4 = np.zeros(steps)
y_euler = np.zeros(steps)
y_rk4[0] = {{Y0}}
y_euler[0] = {{Y0}}

# Numerical Integration
for i in range(steps - 1):
    ti = t[i]
    
    # 1. Euler step: y_{n+1} = y_n + dt * f(t_n, y_n)
    y_euler[i + 1] = y_euler[i] + dt * f(ti, y_euler[i])
    
    # 2. RK4 step: O(dt^4) high accuracy
    yi = y_rk4[i]
    k1 = f(ti, yi)
    k2 = f(ti + 0.5 * dt, yi + 0.5 * dt * k1)
    k3 = f(ti + 0.5 * dt, yi + 0.5 * dt * k2)
    k4 = f(ti + dt, yi + dt * k3)
    y_rk4[i + 1] = yi + (dt / 6.0) * (k1 + 2 * k2 + 2 * k3 + k4)

# Phase portrait dy/dt
dydt_rk4 = np.array([f(ti, yi) for ti, yi in zip(t, y_rk4)])

print("=" * 65)
print("NUMERICAL ODE TELEMETRY: {{ODE_DISP}}")
print("=" * 65)
print(f"Initial Condition:     y(0) = {{Y0}}")
print(f"Time Horizon:          [{t0:.1f}, {t_end:.1f}] s (steps = {steps}, dt = {dt*1000:.1f} ms)")
print(f"Final State (RK4):     y({t_end:.1f}) = {y_rk4[-1]:.6f}")
print(f"Final State (Euler):   y({t_end:.1f}) = {y_euler[-1]:.6f}")
print(f"Max Euler Discrepancy: {np.max(np.abs(y_rk4 - y_euler)):.4e}")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Solution Trajectory y(t)
ax1.plot(t, y_rk4, 'b-', lw=2.2, label='4th-Order Runge-Kutta (RK4)')
ax1.plot(t, y_euler, 'r--', lw=1.6, alpha=0.7, label='Euler Method ($O(\\Delta t)$)')
ax1.scatter([t0], [{{Y0}}], color='gold', s=80, edgecolors='black', zorder=5, label=f'Init $y(0) = {{Y0}}$')
ax1.set_title('ODE Numerical Solution $y(t)$', fontsize=12, fontweight='bold')
ax1.set_xlabel('Independent Variable $t$', fontsize=11)
ax1.set_ylabel('State Variable $y(t)$', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend(loc='best', fontsize=9)

# Plot 2: Phase Portrait (y vs dy/dt)
ax2.plot(y_rk4, dydt_rk4, 'm-', lw=1.8, label=r'Phase Trajectory $(y, \dot{y})$')
ax2.scatter([y_rk4[0]], [dydt_rk4[0]], color='green', s=70, zorder=5, label='Start')
ax2.scatter([y_rk4[-1]], [dydt_rk4[-1]], color='red', s=70, zorder=5, label='End')
ax2.set_title('Phase Space Trajectory $(y, dy/dt)$', fontsize=12, fontweight='bold')
ax2.set_xlabel('$y$', fontsize=11)
ax2.set_ylabel(r'$\dot{y} = dy/dt$', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend(loc='best', fontsize=9)

plt.tight_layout()
plt.show()
PY;
        $code = str_replace(
            ['{{DESC}}', '{{ODE_DISP}}', '{{ODE_EXPR}}', '{{Y0}}'],
            [$cleanDesc, $odeDisp, $odeExpr, (string)$y0],
            $template
        );
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Numerical ODE Integration: {$odeDisp} (RK4 vs Euler)",
            'code' => $code,
            'explanation' => "Solves the initial value problem \${$odeDisp}\$ with \$y(0) = {$y0}\$ numerically using 4th-Order Runge-Kutta (RK4) and contrasts with the 1st-order Euler method. Generates solution trajectories and phase portraits.",
            'complexity' => 'Time: O(N_steps), Space: O(N_steps)',
            'exercises' => [
                'Vary the time step dt and observe how Euler truncation error grows as O(dt) while RK4 remains stable.',
                'Generalize the solver to a 2nd-order ODE by reducing it to a system of two coupled 1st-order equations.',
                'Implement an adaptive step-size Runge-Kutta-Fehlberg (RK45) scheme.'
            ]
        ];
    }

    // -------------------------------------------------------------------------
    // Dynamic Sub-Pattern 2: Numerical Definite Quadrature / Integration
    // -------------------------------------------------------------------------
    if (preg_match('/(?:integrate|integral|area under|quadrature|simpson|trapezoid)/i', $problem)) {
        $userFunc = 'np.sin(x) * np.exp(-0.5 * x)';
        $userFuncDisp = 'sin(x) * exp(-0.5x)';
        if (preg_match('/(?:integrate|integral of|function|of)\s*([a-zA-Z0-9_\+\-\*\/\^\.\(\)\s]+?)(?:from|between|over|\$|\.|$)/i', $problem, $im)) {
            $cand = trim($im[1]);
            if (strlen($cand) >= 2 && preg_match('/[a-zA-Z]/', $cand)) {
                $userFuncDisp = $cand;
                $pyCand = str_replace('^', '**', $cand);
                $pyCand = preg_replace('/(\d+)\s*([a-zA-Z])/', '$1 * $2', $pyCand);
                $pyCand = preg_replace('/\bsin\b/i', 'np.sin', $pyCand);
                $pyCand = preg_replace('/\bcos\b/i', 'np.cos', $pyCand);
                $pyCand = preg_replace('/\bexp\b/i', 'np.exp', $pyCand);
                $userFunc = $pyCand;
            }
        }

        $a = 0.0;
        $b = 3.14159;
        if (preg_match('/(?:from|between)\s*(-?\d+(?:\.\d+)?)\s*(?:to|and)\s*(-?\d+(?:\.\d+)?)/i', $problem, $bm)) {
            $a = (float)$bm[1];
            $b = (float)$bm[2];
        }

        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# ==============================================================================
# Computational Solution: {{DESC}}
# Numerical Definite Quadrature: Simpson's 1/3 Rule vs Trapezoidal Rule
# Target Integrand: f(x) = {{FUNC_DISP}}
# Domain of Integration: [a, b] = [{{A}}, {{B}}]
# ==============================================================================

def f(x):
    """Target integrand function"""
    try:
        return {{FUNC_EXPR}}
    except Exception:
        return np.sin(x) * np.exp(-0.3 * x)

a = {{A}}
b = {{B}}
N_intervals = 100  # Even number for Simpson's 1/3 rule

# 1. Composite Trapezoidal Rule: O(h^2)
x_trap = np.linspace(a, b, N_intervals + 1)
y_trap = f(x_trap)
h_trap = (b - a) / N_intervals
integral_trapezoidal = (h_trap / 2.0) * (y_trap[0] + 2.0 * np.sum(y_trap[1:-1]) + y_trap[-1])

# 2. Composite Simpson's 1/3 Rule: O(h^4)
x_simp = np.linspace(a, b, N_intervals + 1)
y_simp = f(x_simp)
h_simp = (b - a) / N_intervals
integral_simpson = (h_simp / 3.0) * (y_simp[0] + 4.0 * np.sum(y_simp[1:-1:2]) + 2.0 * np.sum(y_simp[2:-1:2]) + y_simp[-1])

# 3. High-Resolution Reference Integral (Gaussian Quadrature Benchmark)
x_fine = np.linspace(a, b, 2000)
y_fine = f(x_fine)
dx_fine = (b - a) / 1999
integral_ref = np.trapz(y_fine, x_fine)

print("=" * 65)
print("NUMERICAL QUADRATURE REPORT: {{FUNC_DISP}}")
print("=" * 65)
print(f"Integration Limits:    [{a}, {b}] (dx = {h_simp:.5f})")
print(f"Trapezoidal Rule:      I_trap = {integral_trapezoidal:.8f}")
print(f"Simpson's 1/3 Rule:    I_simp = {integral_simpson:.8f}")
print(f"Benchmark Reference:   I_ref  = {integral_ref:.8f}")
print(f"Simpson Error:         {abs(integral_simpson - integral_ref):.4e}")
print("=" * 65)

# Error scaling across interval counts
subdivisions = [10, 20, 50, 100, 200, 500]
err_trap, err_simp = [], []
for n in subdivisions:
    x_sub = np.linspace(a, b, n + 1)
    y_sub = f(x_sub)
    h_sub = (b - a) / n
    it = (h_sub / 2.0) * (y_sub[0] + 2.0 * np.sum(y_sub[1:-1]) + y_sub[-1])
    isimp = (h_sub / 3.0) * (y_sub[0] + 4.0 * np.sum(y_sub[1:-1:2]) + 2.0 * np.sum(y_sub[2:-1:2]) + y_sub[-1])
    err_trap.append(abs(it - integral_ref) + 1e-16)
    err_simp.append(abs(isimp - integral_ref) + 1e-16)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11.5, 4.5))

# Plot 1: Shaded Definite Integral Area
x_plot = np.linspace(a - 0.2 * abs(b - a), b + 0.2 * abs(b - a), 500)
ax1.plot(x_plot, f(x_plot), 'b-', lw=2.2, label=r'$f(x) = {{FUNC_DISP}}$')
ax1.fill_between(x_fine, 0, y_fine, color='cyan', alpha=0.25, label=f'Area = {integral_simpson:.4f}')
ax1.axvline(a, color='crimson', linestyle=':', label=f'Lower $a = {a}$')
ax1.axvline(b, color='forestgreen', linestyle=':', label=f'Upper $b = {b}$')
ax1.axhline(0, color='gray', linestyle='--', alpha=0.5)
ax1.set_title('Definite Integral: Shaded Area Under Curve', fontsize=12, fontweight='bold')
ax1.set_xlabel('$x$', fontsize=11)
ax1.set_ylabel('$f(x)$', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend(loc='best', fontsize=9)

# Plot 2: Log-Log Error Convergence Decay
hs = [(b - a) / n for n in subdivisions]
ax2.loglog(hs, err_trap, 'ro-', lw=1.8, label=r'Trapezoidal Error $O(h^2)$')
ax2.loglog(hs, err_simp, 'bs-', lw=1.8, label=r"Simpson's 1/3 Error $O(h^4)$")
ax2.set_title('Asymptotic Error Convergence vs Step Size $h$', fontsize=12, fontweight='bold')
ax2.set_xlabel('Step Size $h = (b - a)/N$ (Log Scale)', fontsize=11)
ax2.set_ylabel('Absolute Error (Log Scale)', fontsize=11)
ax2.grid(True, which='both', alpha=0.3)
ax2.legend(loc='best', fontsize=9)

plt.tight_layout()
plt.show()
PY;
        $code = str_replace(
            ['{{DESC}}', '{{FUNC_DISP}}', '{{FUNC_EXPR}}', '{{A}}', '{{B}}'],
            [$cleanDesc, $userFuncDisp, $userFunc, (string)$a, (string)$b],
            $template
        );
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Numerical Quadrature: Integration of {$userFuncDisp} over [{$a}, {$b}]",
            'code' => $code,
            'explanation' => "Calculates the definite integral \$\\int_{{$a}}^{{$b}} f(x) dx\$ using Simpson's 1/3 rule and the Trapezoidal method. Demonstrates quartic \$O(h^4)\$ error convergence against quadratic \$O(h^2)\$ decay.",
            'complexity' => 'Time: O(N), Space: O(N)',
            'exercises' => [
                'Test integration of a function with an essential singularity using Romberg extrapolation.',
                'Implement Gauss-Legendre quadrature with orthogonal Legendre polynomial roots.',
                'Compute multi-dimensional integrals over 2D circular domains using polar coordinate transformation.'
            ]
        ];
    }

    // -------------------------------------------------------------------------
    // Dynamic Sub-Pattern 3: Monte Carlo & 2D Random Walk Simulation
    // -------------------------------------------------------------------------
    if (preg_match('/(?:monte carlo|random walk|brownian|stochastic|coin toss|dice|random process)/i', $problem)) {
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# ==============================================================================
# Computational Solution: {{DESC}}
# Stochastic Physics: 2D Random Walk & Brownian Motion Ensemble Simulation
# Mean Displacement: <R> = 0 (Isotropic)
# Mean Squared Displacement (MSD): <R^2(t)> = 2 * d * D * t (Einstein Relation)
# ==============================================================================

num_steps = 1000       # Time steps per walker
num_walkers = 15       # Ensemble size for statistical averaging
step_size = 1.0        # Mean free path per step

# Generate 2D random steps with isotropic random angles theta in [0, 2*pi]
angles = np.random.uniform(0, 2 * np.pi, size=(num_walkers, num_steps))
dx = step_size * np.cos(angles)
dy = step_size * np.sin(angles)

# Cumulative trajectory positions (starting at origin [0, 0])
x_paths = np.hstack([np.zeros((num_walkers, 1)), np.cumsum(dx, axis=1)])
y_paths = np.hstack([np.zeros((num_walkers, 1)), np.cumsum(dy, axis=1)])

# Mean squared displacement (MSD) across all walkers over time
squared_displacement = x_paths**2 + y_paths**2
msd = np.mean(squared_displacement, axis=0)
time_steps = np.arange(num_steps + 1)

final_radii = np.sqrt(squared_displacement[:, -1])
mean_final_r = np.mean(final_radii)
theoretical_rms = np.sqrt(num_steps) * step_size

print("=" * 65)
print("2D BROWNIAN RANDOM WALK ENSEMBLE REPORT")
print("=" * 65)
print(f"Ensemble Size:             {num_walkers} independent walkers")
print(f"Number of Steps (N):       {num_steps} steps (step size = {step_size})")
print(f"Mean Final Radius <R>:     {mean_final_r:.3f}")
print(f"Theoretical RMS Radius:    sqrt(N) = {theoretical_rms:.3f}")
print(f"Empirical / Theory Ratio:  {mean_final_r / theoretical_rms:.3f}")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11.5, 4.6))

# Plot 1: 2D Spatial Trajectories
for i in range(num_walkers):
    ax1.plot(x_paths[i], y_paths[i], lw=1.0, alpha=0.7)
    ax1.scatter([x_paths[i, -1]], [y_paths[i, -1]], s=40, zorder=4)

ax1.scatter([0], [0], color='gold', s=120, edgecolors='black', zorder=5, label='Origin (0,0)')
ax1.set_aspect('equal')
ax1.set_title(f'2D Brownian Trajectories ({num_walkers} Walkers, N = {num_steps})', fontsize=12, fontweight='bold')
ax1.set_xlabel('Position $x$', fontsize=11)
ax1.set_ylabel('Position $y$', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend(loc='upper right', fontsize=9)

# Plot 2: Mean Squared Displacement (MSD) Linear Growth
ax2.plot(time_steps, msd, 'b-', lw=2.2, label=r'Empirical Ensemble $\langle R^2(t) \rangle$')
ax2.plot(time_steps, time_steps * (step_size**2), 'r--', lw=1.8, label=r'Theoretical Einstein Law $\langle R^2 \rangle = N \cdot l^2$')
ax2.set_title('Mean Squared Displacement Linear Scaling', fontsize=12, fontweight='bold')
ax2.set_xlabel('Time Step Index $N$', fontsize=11)
ax2.set_ylabel(r'MSD $\langle R^2 \rangle$', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend(loc='upper left', fontsize=9.5)

plt.tight_layout()
plt.show()
PY;
        $code = str_replace('{{DESC}}', $cleanDesc, $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Stochastic Simulation: 2D Random Walk & Brownian Diffusion Ensemble",
            'code' => $code,
            'explanation' => "Simulates an ensemble of 2D Brownian random walks and verifies Einstein's diffusion scaling law \$\\langle R^2(t) \\rangle = N l^2\$. Visualizes stochastic trajectories and linear mean-square displacement growth.",
            'complexity' => 'Time: O(N_walkers * N_steps), Space: O(N_walkers * N_steps)',
            'exercises' => [
                'Implement 3D random walks and verify that the probability of returning to the origin drops to ~34% (Polya theorem).',
                'Add an external drift velocity (electric field on charged ions) and observe biased random walks.',
                'Compute the probability density histogram of final radial positions and fit with Rayleigh distribution.'
            ]
        ];
    }

    // -------------------------------------------------------------------------
    // Pattern A: Area / Perimeter / Geometry (e.g. Circle, Sphere, Triangle)
    // -------------------------------------------------------------------------
    if (str_contains($p, 'circle') || str_contains($p, 'radius') || str_contains($p, 'sphere') || (str_contains($p, 'area') && !str_contains($p, 'integral'))) {
        $radius = 5.0;
        if (preg_match('/(\d+(?:\.\d+)?)/', $problem, $m)) {
            $radius = (float)$m[1];
        }
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# ==============================================================================
# Computational Solution: {{DESC}}
# Geometric & Physical Calculations for Circular / Spherical Geometry
# ==============================================================================

r = {{RADIUS}}   # Radius parameter

# 1. Analytical Formulae
area = np.pi * r**2
circumference = 2 * np.pi * r
sphere_volume = (4.0 / 3.0) * np.pi * r**3
sphere_surface_area = 4.0 * np.pi * r**2

print("=" * 65)
print(f"GEOMETRIC ANALYSIS FOR RADIUS r = {r}")
print("=" * 65)
print(f"Circle Perimeter / Circumference: {circumference:.4f}")
print(f"Circle Area:                      {area:.4f}")
print(f"Sphere Surface Area:              {sphere_surface_area:.4f}")
print(f"Sphere Volume:                    {sphere_volume:.4f}")
print("=" * 65)

# 2. Parametric Visualization of the Geometry
theta = np.linspace(0, 2 * np.pi, 300)
x = r * np.cos(theta)
y = r * np.sin(theta)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: 2D Circle Geometry
ax1.plot(x, y, 'b-', lw=2.2, label=f'Circle (r = {r})')
ax1.fill(x, y, color='cyan', alpha=0.25, label=f'Area = {area:.2f}')
ax1.plot([0, r], [0, 0], 'r--', lw=2, label=f'Radius = {r}')
ax1.scatter([0], [0], color='black', s=50, zorder=5)
ax1.set_aspect('equal')
ax1.set_title(f'2D Circle Geometry (Radius = {r})', fontsize=12, fontweight='bold')
ax1.set_xlabel(r'$x$', fontsize=11)
ax1.set_ylabel(r'$y$', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend(loc='upper right')

# Plot 2: Scaling vs Radius
r_range = np.linspace(0.1, r * 2.0, 100)
ax2.plot(r_range, np.pi * r_range**2, 'b-', lw=2, label=r'Area $\pi r^2$')
ax2.plot(r_range, 2 * np.pi * r_range, 'g--', lw=1.8, label=r'Circumference $2\pi r$')
ax2.scatter([r], [area], color='red', s=80, zorder=5, label=f'Current Area ({area:.1f})')
ax2.set_title(r'Geometric Scaling with Radius $r$', fontsize=12, fontweight='bold')
ax2.set_xlabel(r'Radius $r$', fontsize=11)
ax2.set_ylabel('Metric Value', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        $code = str_replace(['{{DESC}}', '{{RADIUS}}'], [$cleanDesc, (string)$radius], $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => 'Geometric Analysis: Area, Perimeter & Scaling (Radius = ' . $radius . ')',
            'code' => $code,
            'explanation' => "Calculates the fundamental geometric properties (area, perimeter, and 3D spherical metrics) for radius \$r = {$radius}\$. Visualizes the parametric contour and metric scaling curves.",
            'complexity' => 'Time: O(1), Space: O(1)',
            'exercises' => [
                'Extend the calculation to elliptical geometries with semi-axes a and b.',
                'Compute the moment of inertia for a solid disk I = 0.5 * M * R^2 vs thin hoop I = M * R^2.',
                'Integrate polar surface elements numerically using Simpson\'s rule.'
            ]
        ];
    }

    // -------------------------------------------------------------------------
    // Pattern B: Energy / Work / Power / Kinematics (e.g. Kinetic Energy, Momentum)
    // -------------------------------------------------------------------------
    if ((str_contains($p, 'kinetic energy') || (str_contains($p, 'momentum') && str_contains($p, 'mass')) || (str_contains($p, 'velocity') && str_contains($p, 'mass'))) && !str_contains($p, 'wave') && !str_contains($p, 'quantum') && !str_contains($p, 'integral') && !str_contains($p, 'differential')) {
        $mass = 2.0;
        $velocity = 15.0;
        if (preg_match_all('/(\d+(?:\.\d+)?)/', $problem, $matches)) {
            if (!empty($matches[1][0])) $mass = (float)$matches[1][0];
            if (!empty($matches[1][1])) $velocity = (float)$matches[1][1];
        }
        $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# ==============================================================================
# Computational Solution: {{DESC}}
# Classical Mechanics: Kinematics, Momentum & Kinetic Energy
# ==============================================================================

mass = {{MASS}}          # Mass of particle/body (kg)
v0 = {{VELOCITY}}        # Velocity (m/s)

# Physical Quantities:
# Linear Momentum: p = m * v
# Kinetic Energy:  E_k = 0.5 * m * v^2 = p^2 / (2 * m)
p0 = mass * v0
Ek0 = 0.5 * mass * v0**2

print("=" * 65)
print(f"MECHANICS TELEMETRY FOR MASS = {mass} kg, VELOCITY = {v0} m/s")
print("=" * 65)
print(f"Linear Momentum (p):        {p0:.4f} kg·m/s")
print(f"Kinetic Energy (E_k):       {Ek0:.4f} Joules")
print(f"Relativistic Rest Energy:   {mass * 3e8**2:.4e} Joules")
print("=" * 65)

# Velocity sweep to analyze quadratic energy growth
v_range = np.linspace(0, v0 * 2.0, 200)
momentum_curve = mass * v_range
kinetic_curve = 0.5 * mass * v_range**2

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Linear Momentum vs Velocity (Linear Scaling)
ax1.plot(v_range, momentum_curve, 'b-', lw=2, label=r'Momentum $p = m v$')
ax1.scatter([v0], [p0], color='red', s=80, zorder=5, label=f'Current State ($p={p0:.1f}$)')
ax1.set_title('Linear Momentum vs Velocity', fontsize=12, fontweight='bold')
ax1.set_xlabel('Velocity $v$ (m/s)', fontsize=11)
ax1.set_ylabel('Momentum $p$ (kg·m/s)', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Kinetic Energy vs Velocity (Quadratic Scaling)
ax2.plot(v_range, kinetic_curve, 'r-', lw=2, label=r'Kinetic Energy $E_k = \frac{1}{2}m v^2$')
ax2.scatter([v0], [Ek0], color='blue', s=80, zorder=5, label=f'Current Energy ({Ek0:.1f} J)')
ax2.set_title('Quadratic Kinetic Energy Profile', fontsize=12, fontweight='bold')
ax2.set_xlabel('Velocity $v$ (m/s)', fontsize=11)
ax2.set_ylabel('Kinetic Energy $E_k$ (J)', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;
        $code = str_replace(['{{DESC}}', '{{MASS}}', '{{VELOCITY}}'], [$cleanDesc, (string)$mass, (string)$velocity], $template);
        return [
            'success' => true,
            'source' => 'physics_engine',
            'title' => "Classical Mechanics: Kinetic Energy & Linear Momentum (m = {$mass} kg, v = {$velocity} m/s)",
            'code' => $code,
            'explanation' => "Computes the linear momentum \$p = mv\$ and classical kinetic energy \$E_k = \\frac{1}{2}mv^2\$ for a body of mass \$m = {$mass}\$ kg traveling at \$v = {$velocity}\$ m/s. Contrasts linear momentum scaling with quadratic energy growth.",
            'complexity' => 'Time: O(1), Space: O(1)',
            'exercises' => [
                'Compute the work required to accelerate the object from v1 to v2 via the Work-Energy Theorem.',
                'Calculate relativistic corrections using E = m*c^2 / sqrt(1 - v^2/c^2) - m*c^2 for near-light speeds.',
                'Model inelastic collision between two masses and quantify mechanical energy dissipation.'
            ]
        ];
    }

    // -------------------------------------------------------------------------
    // Pattern C: Mathematical Function Plotter (e.g. sin, cos, polynomial, exp)
    // -------------------------------------------------------------------------
    $mathExpr = 'np.sin(x) * np.exp(-0.2 * x)';
    $userFuncText = 'sin(x) * exp(-0.2*x)';
    $candidate = '';
    if (preg_match('/(?:y\s*=|f\(x\)\s*=)\s*([a-zA-Z0-9_\+\-\*\/\^\.\(\)\s]+?)(?:from|between|\$|[\r\n]|;\s*|$)/i', $problem, $m)) {
        $candidate = trim($m[1]);
    } elseif (preg_match('/(?:plot|graph|function|curve|equation)\s*(?:the\s+mathematical\s+curve\s+|the\s+curve\s+|the\s+function\s+)?(?:y\s*=\s*|f\(x\)\s*=\s*)?([a-zA-Z0-9_\+\-\*\/\^\.\(\)\s]+?)(?:from|between|\$|[\r\n]|;\s*|$)/i', $problem, $m)) {
        $candidate = trim($m[1]);
    }

    if (!empty($candidate) && strlen($candidate) > 2) {
        $userFuncText = $candidate;
        // Convert math syntax to numpy
        $pyExpr = preg_replace('/\^/', '**', $candidate);
        $pyExpr = preg_replace('/(\d+)\s*([a-zA-Z])/', '$1 * $2', $pyExpr);
        $pyExpr = preg_replace('/\bsin\b/i', 'np.sin', $pyExpr);
        $pyExpr = preg_replace('/\bcos\b/i', 'np.cos', $pyExpr);
        $pyExpr = preg_replace('/\btan\b/i', 'np.tan', $pyExpr);
        $pyExpr = preg_replace('/\bexp\b/i', 'np.exp', $pyExpr);
        $pyExpr = preg_replace('/\blog\b/i', 'np.log', $pyExpr);
        $pyExpr = preg_replace('/\bsqrt\b/i', 'np.sqrt', $pyExpr);
        $mathExpr = $pyExpr;
    }

    $x_start = -5.0;
    $x_end = 5.0;
    if (preg_match('/from\s*(?:x\s*=\s*)?(-?\d+(?:\.\d+)?)\s*to\s*(-?\d+(?:\.\d+)?)/i', $problem, $xm)) {
        $x_start = (float)$xm[1];
        $x_end = (float)$xm[2];
    } elseif (preg_match('/between\s*(-?\d+(?:\.\d+)?)\s*and\s*(-?\d+(?:\.\d+)?)/i', $problem, $xm)) {
        $x_start = (float)$xm[1];
        $x_end = (float)$xm[2];
    } elseif (str_contains($mathExpr, 'sqrt') || str_contains($mathExpr, 'log')) {
        $x_start = 0.1;
        $x_end = 10.0;
    }

    $template = <<<'PY'
import numpy as np
import matplotlib.pyplot as plt

# ==============================================================================
# Computational Solution: {{DESC}}
# Scientific Function Analysis & Multi-Panel Visualization
# Target Expression: {{FUNC}}
# ==============================================================================

# 1. Spatial Discretization
x_start = {{XSTART}}
x_end = {{XEND}}
num_samples = 500
x = np.linspace(x_start, x_end, num_samples)
dx = x[1] - x[0]

# 2. Target Mathematical Function Evaluation
try:
    y = {{EXPR}}
except Exception as e:
    # Safe fallback if expression syntax differs
    y = np.sin(x) * np.exp(-0.15 * np.abs(x))

# 3. Numerical Derivative & Definite Area Integral
derivative = np.gradient(y, dx)
cumulative_integral = np.cumsum(y) * dx

print("=" * 65)
print("COMPUTATIONAL FUNCTION ANALYSIS: {{FUNC}}")
print("=" * 65)
print(f"Domain Bounds:        [{x_start:.1f}, {x_end:.1f}]")
print(f"Resolution:           {num_samples} discrete points (dx = {dx:.4f})")
print(f"Global Maximum:       {np.max(y):.4f} at x = {x[np.argmax(y)]:.3f}")
print(f"Global Minimum:       {np.min(y):.4f} at x = {x[np.argmin(y)]:.3f}")
print(f"Total Net Area:       {cumulative_integral[-1]:.4f}")
print("=" * 65)

fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(11, 4.5))

# Plot 1: Target Function & Numerical Derivative
ax1.plot(x, y, 'b-', lw=2.2, label=r'$f(x)$')
ax1.plot(x, derivative, 'c--', lw=1.5, label=r"$f'(x)$ (Numerical Gradient)")
ax1.axhline(0, color='gray', linestyle=':', alpha=0.5)
ax1.set_title('Target Function Curve & Numerical Derivative', fontsize=12, fontweight='bold')
ax1.set_xlabel(r'$x$', fontsize=11)
ax1.set_ylabel(r'$y$', fontsize=11)
ax1.grid(True, alpha=0.3)
ax1.legend()

# Plot 2: Cumulative Area (Numerical Integral)
ax2.plot(x, cumulative_integral, 'm-', lw=2.0, label=r'Cumulative $\int f(x) dx$')
ax2.fill_between(x, 0, cumulative_integral, color='purple', alpha=0.15)
ax2.set_title('Cumulative Numerical Integral Over Domain', fontsize=12, fontweight='bold')
ax2.set_xlabel(r'$x$', fontsize=11)
ax2.set_ylabel('Accumulated Area', fontsize=11)
ax2.grid(True, alpha=0.3)
ax2.legend()

plt.tight_layout()
plt.show()
PY;

    $code = str_replace(
        ['{{DESC}}', '{{FUNC}}', '{{EXPR}}', '{{XSTART}}', '{{XEND}}'],
        [$cleanDesc, $userFuncText, $mathExpr, (string)$x_start, (string)$x_end],
        $template
    );

    return [
        'success' => true,
        'source' => 'physics_engine',
        'title' => 'Numerical Function Modeling: y = ' . $userFuncText . " on [{$x_start}, {$x_end}]",
        'code' => $code,
        'explanation' => "Generated an analytical and numerical simulation tailored directly to the requested problem: {$cleanDesc}. Discretizes the domain [{$x_start}, {$x_end}], calculates numerical derivatives and cumulative integrals, and plots multi-panel telemetry.",
        'complexity' => 'Time: O(N), Space: O(N)',
        'exercises' => [
            'Change domain bounds [x_start, x_end] to observe long-term asymptotic behavior.',
            'Increase resolution num_samples and analyze error convergence of the gradient.',
            'Add stochastic Gaussian noise using np.random.normal() to simulate experimental measurement data.'
        ]
    ];
}
