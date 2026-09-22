            <div class='font-size-selector' style = 'justify-content: flex-end;'>
                <label for='fontSizeSelect'>Font Size:</label>
                <select id='fontSizeSelect' onchange='updateFontSize()'>
                    <option value='10'>10px</option>
					<option value='12'>12px</option>
                    <option value='14'>14px</option>
                    <option value='16'>16px</option>
                    <option value='18'>18px</option>
                    <option value='20'>20px</option>
                </select>
            </div>
			<script>
			function setFontSizeBasedOnWidth() {
			const width = window.innerWidth;
			const fontSizeSelect = document.getElementById('fontSizeSelect');

		if (width < 1168) {
			fontSizeSelect.value = '12';
			} else if (width < 1550) {
			fontSizeSelect.value = '14';
			} else {
			fontSizeSelect.value = '18';
			}
		
		// Update font size in CodeMirror instance based on selected value
		updateFontSize(fontSizeSelect.value);
		}

		// Event listener for window resize to adjust font size dynamically
		window.addEventListener('resize', setFontSizeBasedOnWidth);

		// Initial call to set the font size on page load
		setFontSizeBasedOnWidth();
		</script>