<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Authorship Forensics Portal</title>
    <link rel="icon" type="image/x-icon" href="../../favicon.ico">
    <!-- Favicons -->
    <link href="../../assets/img/favicon.ico" rel="icon">
    <link href="../../assets/img/apple-touch-icon.png" rel="apple-touch-icon">
    <!-- Vendor CSS Files -->
    <link href="../../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
    <!-- Template Main CSS File -->
    <link href="../../assets/css/stylesheet.css" rel="stylesheet">
    <link href="../../assets/vendor/font_awesome/css/all.min.css" rel="stylesheet">
    <link href="../css/af_styles.css" rel="stylesheet">
</head>

<body>
    <div id="loader"></div>
    <!-- Custom Responsive CSS -->
    <style>
        body {
            font-size: 16px;
            line-height: 1.5;
        }

        .container {
            padding: 4rem;
        }

        h1 {
            font-size: clamp(1.5rem, 5vw, 2rem);
            margin-bottom: 1.5rem;
        }

        .feature-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .feature-section-components,
        .section-components {
            width: 100%;
        }

        select,
        textarea {
            width: 100%;
            font-size: 1rem;
            padding: 0.75rem;
        }

        textarea {
            min-height: 150px;
        }

        .btn-primary {
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            width: 100%;
            max-width: 300px;
            margin: 1rem auto;
        }

        #result {
            font-size: 1rem;
            padding: 1rem;
        }

        .logo-image {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 1rem auto;
        }

        #loader {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            border: 16px solid #f3f3f3;
            border-radius: 50%;
            border-top: 16px solid #3498db;
            width: 120px;
            height: 120px;
            z-index: 100;
            -webkit-animation: spin 2s linear infinite;
            animation: spin 2s linear infinite;
        }

        @-webkit-keyframes spin {
            0% {
                -webkit-transform: rotate(0deg);
            }

            100% {
                -webkit-transform: rotate(360deg);
            }
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .results-container {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .feature-result {
            border: 1px solid #ddd;
            padding: 1rem;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .prediction-chart {
            max-width: 100%;
            height: auto;
            margin-top: 1rem;
        }

        h3 {
            color: #333;
            margin-bottom: 1rem;
        }

        @media (max-width: 576px) {
            body {
                font-size: 14px;
            }

            .container {
                padding: 0.5rem;
            }

            h1 {
                font-size: 1.5rem;
            }

            select,
            textarea {
                padding: 0.5rem;
            }

            .btn-primary {
                padding: 0.5rem 1rem;
            }
        }

        #view {
            width: 100%;
            height: 400px;
            border: none;
        }
    </style>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mb-4 text-decoration-underline">
                    Authorship Forensics Portal
                    <?php if (isset($_SESSION['user_name'])) echo '<br>User: ' . $_SESSION['user_name']; ?>
                </h1>
                <div class="bg-white shadow-md rounded-lg py-6 px-4 mx-auto">
                    <div class="feature-section">
                        <!-- Dataset Selection -->
                        <div class="feature-section-components mb-4">
                            <label for="datasetSelect" class="block font-medium mb-1">Select Dataset:</label>
                            <select id="datasetSelect" class="form-select border border-gray-300 rounded"></select>
                        </div>
                        <!-- Model Selection -->
                        <div class="feature-section-components mb-4">
                            <label for="modelSelect" class="block font-medium mb-1">Select Trained Model:</label>
                            <select id="modelSelect" class="form-select border border-gray-300 rounded"></select>
                        </div>
                        <!-- Feature Set Selection -->
                        <div class="feature-section-components mb-4">
                            <label for="featureSetSelect" class="block font-medium mb-1">Select Feature Set:</label>
                            <select id="featureSetSelect" class="form-select border border-gray-300 rounded"></select>
                        </div>
                        <!-- Text Input -->
                        <div class="section-components">
                            <div class="mb-4">
                                <label for="inputText" class="block font-medium mb-1">Enter or Paste Text:</label>
                                <textarea id="inputText" rows="8" placeholder="Enter the text here..." class="form-control border border-gray-300 rounded"></textarea>
                            </div>
                            <!-- Submit Button -->
                            <div class="text-center">
                                <button onclick="predictAuthorAndList()" id="predictAuthorBtn" class="btn btn-primary" disabled>Tell me the potential author</button>
                            </div>
                        </div>
                        <!-- Result Display -->
                        <div id="result" class="mt-4 text-center font-semibold text-green-700 hidden"></div>
                        <div class="mb-4">
                            <iframe src="https://ngrampos.vipresearch.ca/" id="view" frameborder="0"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../../assets/js/jquery-3.7.1.min.js"></script>
    <script>
        let api_keys = <?php echo json_encode($api_keys); ?>;

        // Performance monitoring
        const performanceObserver = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (entry.duration > 1000) {
                    console.warn('Slow network detected for ' + entry.name + ': ' + entry.duration + 'ms');
                }
            }
        });
        performanceObserver.observe({
            entryTypes: ['resource']
        });

        // Enable predict button only when all inputs are valid
        function updateButtonState() {
            const datasetSelect = document.getElementById('datasetSelect').value;
            const modelSelect = document.getElementById('modelSelect').value;
            const featureSetSelect = document.getElementById('featureSetSelect').value;
            const inputText = document.getElementById('inputText').value.trim();
            document.getElementById('predictAuthorBtn').disabled = !datasetSelect || !modelSelect || !featureSetSelect || !inputText;
        }

        // Add event listeners for input changes
        document.getElementById('datasetSelect').addEventListener('change', updateButtonState);
        document.getElementById('modelSelect').addEventListener('change', updateButtonState);
        document.getElementById('featureSetSelect').addEventListener('change', updateButtonState);
        document.getElementById('inputText').addEventListener('input', updateButtonState);

        // Load datasets and models
        $.ajax({
            url: '../list_model_options.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('list_model_options response:', response);
                if (response.success && Array.isArray(response.datasets)) {
                    const datasetSelect = document.getElementById('datasetSelect');
                    const modelSelect = document.getElementById('modelSelect');
                    const featureSetSelect = document.getElementById('featureSetSelect');

                    // Clear existing options
                    datasetSelect.innerHTML = '';
                    modelSelect.innerHTML = '';
                    featureSetSelect.innerHTML = '';

                    // Add default options
                    const defaultDatasetOption = document.createElement('option');
                    defaultDatasetOption.value = '';
                    defaultDatasetOption.textContent = 'Select a dataset';
                    defaultDatasetOption.disabled = true;
                    defaultDatasetOption.selected = true;
                    datasetSelect.appendChild(defaultDatasetOption);

                    const defaultModelOption = document.createElement('option');
                    defaultModelOption.value = '';
                    defaultModelOption.textContent = 'Select a model';
                    defaultModelOption.disabled = true;
                    defaultModelOption.selected = true;
                    modelSelect.appendChild(defaultModelOption);

                    const defaultFeatureSetOption = document.createElement('option');
                    defaultFeatureSetOption.value = '';
                    defaultFeatureSetOption.textContent = 'Select a feature set';
                    defaultFeatureSetOption.disabled = true;
                    defaultFeatureSetOption.selected = true;
                    featureSetSelect.appendChild(defaultFeatureSetOption);

                    // Add dataset options
                    response.datasets.forEach(dataset => {
                        const option = document.createElement('option');
                        option.value = dataset.dataset_label;
                        option.textContent = dataset.dataset_label;
                        datasetSelect.appendChild(option);
                    });

                    // Handle dataset selection change
                    datasetSelect.addEventListener('change', function(event) {
                        const mainValue = event.target.value;
                        console.log('Dataset selected:', mainValue);
                        const selectedDataset = response.datasets.find(d => d.dataset_label === mainValue);
                        console.log('Selected dataset object:', selectedDataset);

                        // Clear and populate feature set options
                        featureSetSelect.innerHTML = '';
                        featureSetSelect.appendChild(defaultFeatureSetOption.cloneNode(true));

                        // Define available feature sets
                        const availableFeatureSets = ['all', 'top5', 'top15', 'top50'];
                        console.log('Available feature sets:', availableFeatureSets);

                        // Add feature set options
                        availableFeatureSets.forEach(feature => {
                            const option = document.createElement('option');
                            option.value = feature;
                            option.textContent = feature === 'all' ? 'All Features' : 'Top ' + feature.replace('top', '');
                            featureSetSelect.appendChild(option);
                        });

                        // Auto-select the highest top-x feature set (top50 > top15 > top5)
                        const preferredFeature = ['top50', 'top15', 'top5'].find(f => availableFeatureSets.includes(f)) || 'all';
                        const preferredOption = Array.from(featureSetSelect.options).find(opt => opt.value === preferredFeature);
                        if (preferredOption) {
                            preferredOption.selected = true;
                        }

                        // Clear and populate model options
                        modelSelect.innerHTML = '';
                        modelSelect.appendChild(defaultModelOption.cloneNode(true));

                        if (selectedDataset && selectedDataset.models) {
                            const models = Array.isArray(selectedDataset.models) ? selectedDataset.models : [];
                            console.log('Available models for dataset:', models);
                            models.forEach(model => {
                                const modelName = typeof model === 'string' ? model : model.model_name || model;
                                const option = document.createElement('option');
                                option.value = modelName;
                                option.textContent = modelName;
                                modelSelect.appendChild(option);
                            });

                            if (models.length === 0) {
                                const noModelOption = document.createElement('option');
                                noModelOption.value = '';
                                noModelOption.textContent = 'No models available';
                                noModelOption.disabled = true;
                                modelSelect.appendChild(noModelOption);
                            }
                        } else {
                            const noModelOption = document.createElement('option');
                            noModelOption.value = '';
                            noModelOption.textContent = 'No models available';
                            noModelOption.disabled = true;
                            modelSelect.appendChild(noModelOption);
                        }
                        updateButtonState();
                    });
                } else {
                    console.error('No datasets found or response was unsuccessful:', response);
                    const datasetSelect = document.getElementById('datasetSelect');
                    datasetSelect.innerHTML = '';
                    const noDatasetOption = document.createElement('option');
                    noDatasetOption.value = '';
                    noDatasetOption.textContent = 'No datasets available';
                    noDatasetOption.disabled = true;
                    noDatasetOption.selected = true;
                    datasetSelect.appendChild(noDatasetOption);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching model options:', error);
                alert('Failed to load datasets and models.');
            }
        });

        // Helper function for making prediction requests with retry logic
        async function makePredictionRequest(retryCount = 0) {
            const maxRetries = 3;
            const baseTimeout = 30000; // 30 seconds

            try {
                const predictionResponse = await new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    const timeout = baseTimeout * (retryCount + 1);

                    const timeoutId = setTimeout(() => {
                        xhr.abort();
                        reject(new Error('Request timed out'));
                    }, timeout);

                    xhr.open('POST', '../sp_make_prediction.php', true);
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            clearTimeout(timeoutId);
                            if (xhr.status === 200) {
                                if (xhr.responseText.includes('Predicted Author:')) {
                                    resolve(xhr.responseText);
                                } else {
                                    reject(new Error('Incomplete prediction response'));
                                }
                            } else {
                                reject(new Error('Request failed with status ' + xhr.status));
                            }
                        }
                    };
                    xhr.onerror = () => {
                        clearTimeout(timeoutId);
                        reject(new Error('Network error'));
                    };
                    xhr.send();
                });

                return predictionResponse;
            } catch (error) {
                if (retryCount < maxRetries) {
                    console.log('Retry attempt ' + (retryCount + 1) + ' for prediction request');
                    await new Promise(r => setTimeout(r, 2000));
                    return makePredictionRequest(retryCount + 1);
                }
                throw error;
            }
        }

        // Helper function for parsing prediction response
        function parsePredictionResponse(responseText, featureSet) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(responseText, 'text/html');

            console.log('Parsing prediction response for feature set:', featureSet);
            console.log('Raw response text:', responseText);

            const allPredictions = {};
            const allChartImageSrcs = {};

            // Log the DOM structure for debugging
            const serializer = new XMLSerializer();
            const domString = serializer.serializeToString(doc);
            console.log('Parsed DOM structure:', domString);

            // Iterate through all h2 elements that contain 'Predicted Author:'
            doc.querySelectorAll('h2').forEach(h2 => {
                if (h2.textContent.includes('Predicted Author:')) {
                    const author = h2.textContent.replace('Predicted Author:', '').trim();
                    console.log('Found h2 with Predicted Author:', author);

                    // Find the nearest preceding h1 to determine the feature set
                    let h1 = h2.previousElementSibling;
                    while (h1 && h1.tagName !== 'H1') {
                        h1 = h1.previousElementSibling;
                    }

                    if (h1) {
                        const sectionTitle = h1.textContent.trim();
                        console.log('Section title found:', sectionTitle);

                        // Robust matching for feature sets
                        const lowerSectionTitle = sectionTitle.toLowerCase();
                        if (lowerSectionTitle.includes('top_5') || lowerSectionTitle.includes('top 5')) {
                            allPredictions['top5'] = author;
                            console.log('Assigned Top_5 prediction:', author);
                        } else if (lowerSectionTitle.includes('top_15') || lowerSectionTitle.includes('top 15')) {
                            allPredictions['top15'] = author;
                            console.log('Assigned Top_15 prediction:', author);
                        } else if (lowerSectionTitle.includes('top_50') || lowerSectionTitle.includes('top 50') || lowerSectionTitle.includes('top50')) {
                            allPredictions['top50'] = author;
                            console.log('Assigned Top_50 prediction:', author);
                        } else {
                            console.warn('Unrecognized section title:', sectionTitle);
                        }

                        // Find the chart image
                        let img = h1.nextElementSibling;
                        while (img && img.tagName !== 'IMG') {
                            img = img.nextElementSibling;
                        }
                        if (img && img.tagName === 'IMG') {
                            const imgSrc = img.getAttribute('src');
                            if (lowerSectionTitle.includes('top_5') || lowerSectionTitle.includes('top 5')) {
                                allChartImageSrcs['top5'] = imgSrc;
                                console.log('Assigned Top_5 chart:', imgSrc);
                            } else if (lowerSectionTitle.includes('top_15') || lowerSectionTitle.includes('top 15')) {
                                allChartImageSrcs['top15'] = imgSrc;
                                console.log('Assigned Top_15 chart:', imgSrc);
                            } else if (lowerSectionTitle.includes('top_50') || lowerSectionTitle.includes('top 50') || lowerSectionTitle.includes('top50')) {
                                allChartImageSrcs['top50'] = imgSrc;
                                console.log('Assigned Top_50 chart:', imgSrc);
                            }
                        } else {
                            console.log('No chart image found for section:', sectionTitle);
                        }
                    } else {
                        console.warn('No preceding h1 found for author:', author);
                    }
                }
            });

            // Fallback: If top50 is not found, try to find any author prediction
            if (!allPredictions['top50'] && featureSet === 'top50') {
                console.warn('No top50 prediction found, attempting fallback parsing');
                const authorElements = doc.querySelectorAll('h2');
                for (const h2 of authorElements) {
                    if (h2.textContent.includes('Predicted Author:')) {
                        const author = h2.textContent.replace('Predicted Author:', '').trim();
                        allPredictions['top50'] = author;
                        console.log('Fallback: Assigned Top_50 prediction:', author);
                        break;
                    }
                }
            }

            console.log('Final parsed predictions:', allPredictions);
            console.log('Final parsed chart images:', allChartImageSrcs);

            // Return data based on the selected feature set
            if (featureSet === 'all') {
                return {
                    predictions: allPredictions,
                    chartImageSrcs: allChartImageSrcs
                };
            } else {
                return {
                    predictedAuthor: allPredictions[featureSet] || 'Unknown',
                    chartImageSrc: allChartImageSrcs[featureSet] || ''
                };
            }
        }

        // Main prediction function
        window.predictAuthorAndList = async function() {
            const predictBtn = document.getElementById('predictAuthorBtn');
            const resultDiv = document.getElementById('result');
            const loader = document.getElementById('loader');
            const dataset = document.getElementById('datasetSelect').value;
            const model = document.getElementById('modelSelect').value;
            const featureSet = document.getElementById('featureSetSelect').value;
            const text = document.getElementById('inputText').value.trim();

            if (predictBtn.textContent === 'Close') {
                resultDiv.classList.add('hidden');
                predictBtn.textContent = 'Tell me the potential author';
                return;
            }

            if (!dataset || !model || !featureSet || !text) {
                alert('Please select a dataset, model, feature set, and enter text.');
                return;
            }

            console.log('Input values:', {
                dataset,
                model,
                featureSet,
                text
            });

            loader.style.display = 'block';
            resultDiv.classList.add('hidden');
            predictBtn.disabled = true;

            try {
                const formData = new FormData();
                formData.append('textBlock', text);
                formData.append('dataset', dataset);
                formData.append('model', model);
                formData.append('featureSet', featureSet);
                formData.append('authorName', 'Anonymous');

                console.log('FormData contents:');
                for (let [key, value] of formData.entries()) {
                    console.log(key + ': ' + value);
                }

                const prepareDataResponse = await new Promise((resolve, reject) => {
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', '../sp_prepare_data.php', true);
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            console.log('sp_prepare_data.php status:', xhr.status);
                            console.log('sp_prepare_data.php response:', xhr.responseText);
                            if (xhr.status === 200) {
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    console.log('Parsed prepare data response:', response);
                                    resolve(response);
                                } catch (e) {
                                    reject(new Error('Invalid JSON from sp_prepare_data.php: ' + xhr.responseText));
                                }
                            } else {
                                reject(new Error('sp_prepare_data.php failed with status ' + xhr.status));
                            }
                        }
                    };
                    xhr.onerror = () => reject(new Error('Network error during sp_prepare_data.php request'));
                    xhr.send(formData);
                });

                if (!prepareDataResponse.success) {
                    throw new Error(prepareDataResponse.message || 'Failed to prepare data');
                }

                let chartUrl = '../sp_generate_and_display_results.php?featureSet=' + encodeURIComponent(featureSet);
                console.log('Setting iframe src to:', chartUrl);
                document.getElementById('view').src = chartUrl;

                await new Promise(r => setTimeout(r, 5000));
                const predictionResponse = await makePredictionRequest();
                const predictionData = parsePredictionResponse(predictionResponse, featureSet);

                console.log('Final prediction:', predictionData);

                loader.style.display = 'none';
                predictBtn.textContent = 'Close';

                if (featureSet === 'all') {
                    let resultHtml = '<div class="results-container">';
                    ['top50', 'top15', 'top5'].forEach(set => {
                        if (predictionData.predictions[set]) {
                            resultHtml += '<div class="feature-result">' +
                                '<h3>Top ' + set.replace('top', '') + ' Features</h3>' +
                                '<div class="alert alert-success">' +
                                'Predicted Author: ' + predictionData.predictions[set] +
                                '</div>' +
                                (predictionData.chartImageSrcs[set] ?
                                    '<img src="' + predictionData.chartImageSrcs[set] + '" class="prediction-chart" alt="' + set + ' Prediction Chart">' :
                                    '') +
                                '</div>';
                        }
                    });
                    resultHtml += '</div>';
                    resultDiv.innerHTML = resultHtml;
                } else {
                    resultDiv.innerHTML = '<div class="feature-result">' +
                        '<h3>' + (featureSet === 'all' ? 'All Features' : featureSet) + '</h3>' +
                        '<div class="alert alert-success">' +
                        'Predicted Author: ' + predictionData.predictedAuthor +
                        '</div>' +
                        (predictionData.chartImageSrc ?
                            '<img src="' + predictionData.chartImageSrc + '" class="prediction-chart" alt="Prediction Chart">' :
                            '') +
                        '</div>';
                }
                resultDiv.classList.remove('hidden');

            } catch (error) {
                console.error('Prediction error:', error.message, error.stack);
                loader.style.display = 'none';
                resultDiv.innerHTML = '<div class="alert alert-warning">Error: ' + (error.message || 'An error occurred during prediction') + '</div>';
                resultDiv.classList.remove('hidden');
            } finally {
                predictBtn.disabled = false;
                updateButtonState();
            }
        };
    </script>
    <script src="../../assets/vendor/bootstrap4.5.2/bootstrap.min.js"></script>
</body>

</html>
