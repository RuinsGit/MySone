<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lizz API Test</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .card {
            margin-bottom: 20px;
        }
        .loading {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Lizz API Test</h1>
        
        <div class="row">
            <div class="col-md-12">
                <ul class="nav nav-tabs" id="apiTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="generate-tab" data-bs-toggle="tab" data-bs-target="#generate" type="button" role="tab" aria-controls="generate" aria-selected="true">Generate Content</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="code-tab" data-bs-toggle="tab" data-bs-target="#code" type="button" role="tab" aria-controls="code" aria-selected="false">Generate Code</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="chat-tab" data-bs-toggle="tab" data-bs-target="#chat" type="button" role="tab" aria-controls="chat" aria-selected="false">Chat Response</button>
                    </li>
                </ul>
                
                <div class="tab-content" id="apiTabsContent">
                    <!-- Generate Content Tab -->
                    <div class="tab-pane fade show active" id="generate" role="tabpanel" aria-labelledby="generate-tab">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Generate Content</h5>
                                <form id="generateForm">
                                    <div class="mb-3">
                                        <label for="prompt" class="form-label">Prompt</label>
                                        <textarea class="form-control" id="prompt" rows="3" placeholder="Merhaba, nasılsın?"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="temperature" class="form-label">Temperature (0.0 - 1.0)</label>
                                        <input type="range" class="form-range" id="temperature" min="0" max="1" step="0.1" value="0.7">
                                        <span id="temperatureValue">0.7</span>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Gönder</button>
                                    <div class="spinner-border text-primary loading" role="status">
                                        <span class="visually-hidden">Yükleniyor...</span>
                                    </div>
                                </form>
                                
                                <div class="mt-4">
                                    <h6>Response:</h6>
                                    <pre id="generateResponse">API yanıtı burada görüntülenecek...</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Generate Code Tab -->
                    <div class="tab-pane fade" id="code" role="tabpanel" aria-labelledby="code-tab">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Generate Code</h5>
                                <form id="codeForm">
                                    <div class="mb-3">
                                        <label for="codePrompt" class="form-label">Code Prompt</label>
                                        <textarea class="form-control" id="codePrompt" rows="3" placeholder="PHP ile kullanıcı girişi formu oluştur"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="language" class="form-label">Language</label>
                                        <select class="form-select" id="language">
                                            <option value="php">PHP</option>
                                            <option value="javascript">JavaScript</option>
                                            <option value="python">Python</option>
                                            <option value="java">Java</option>
                                            <option value="csharp">C#</option>
                                            <option value="cpp">C++</option>
                                            <option value="ruby">Ruby</option>
                                            <option value="go">Go</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Gönder</button>
                                    <div class="spinner-border text-primary loading" role="status">
                                        <span class="visually-hidden">Yükleniyor...</span>
                                    </div>
                                </form>
                                
                                <div class="mt-4">
                                    <h6>Code Response:</h6>
                                    <pre id="codeResponse">API yanıtı burada görüntülenecek...</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chat Response Tab -->
                    <div class="tab-pane fade" id="chat" role="tabpanel" aria-labelledby="chat-tab">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Chat Response</h5>
                                <form id="chatForm">
                                    <div class="mb-3">
                                        <label for="message" class="form-label">Message</label>
                                        <textarea class="form-control" id="message" rows="3" placeholder="Merhaba, nasılsın?"></textarea>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="creativeMode">
                                        <label class="form-check-label" for="creativeMode">
                                            Creative Mode
                                        </label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="codingMode">
                                        <label class="form-check-label" for="codingMode">
                                            Coding Mode
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Gönder</button>
                                    <div class="spinner-border text-primary loading" role="status">
                                        <span class="visually-hidden">Yükleniyor...</span>
                                    </div>
                                </form>
                                
                                <div class="mt-4">
                                    <h6>Chat Response:</h6>
                                    <pre id="chatResponse">API yanıtı burada görüntülenecek...</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Temperature slider value
            $('#temperature').on('input', function() {
                $('#temperatureValue').text($(this).val());
            });
            
            // Generate Content Form
            $('#generateForm').on('submit', function(e) {
                e.preventDefault();
                const prompt = $('#prompt').val();
                const temperature = parseFloat($('#temperature').val());
                
                if (!prompt) {
                    alert('Lütfen bir prompt girin!');
                    return;
                }
                
                // Show loading
                $(this).find('.loading').show();
                $(this).find('button').prop('disabled', true);
                
                $.ajax({
                    url: '/api-test/lizz',
                    type: 'POST',
                    data: {
                        prompt: prompt,
                        temperature: temperature
                    },
                    success: function(response) {
                        $('#generateResponse').text(JSON.stringify(response, null, 2));
                    },
                    error: function(xhr) {
                        $('#generateResponse').text('Hata: ' + JSON.stringify(xhr.responseJSON, null, 2));
                    },
                    complete: function() {
                        $('#generateForm').find('.loading').hide();
                        $('#generateForm').find('button').prop('disabled', false);
                    }
                });
            });
            
            // Generate Code Form
            $('#codeForm').on('submit', function(e) {
                e.preventDefault();
                const prompt = $('#codePrompt').val();
                const language = $('#language').val();
                
                if (!prompt) {
                    alert('Lütfen bir kod prompt\'u girin!');
                    return;
                }
                
                // Show loading
                $(this).find('.loading').show();
                $(this).find('button').prop('disabled', true);
                
                $.ajax({
                    url: '/api-test/generate-code',
                    type: 'POST',
                    data: {
                        prompt: prompt,
                        language: language
                    },
                    success: function(response) {
                        $('#codeResponse').text(JSON.stringify(response, null, 2));
                    },
                    error: function(xhr) {
                        $('#codeResponse').text('Hata: ' + JSON.stringify(xhr.responseJSON, null, 2));
                    },
                    complete: function() {
                        $('#codeForm').find('.loading').hide();
                        $('#codeForm').find('button').prop('disabled', false);
                    }
                });
            });
            
            // Chat Response Form
            $('#chatForm').on('submit', function(e) {
                e.preventDefault();
                const message = $('#message').val();
                const creativeMode = $('#creativeMode').is(':checked');
                const codingMode = $('#codingMode').is(':checked');
                
                if (!message) {
                    alert('Lütfen bir mesaj girin!');
                    return;
                }
                
                // Show loading
                $(this).find('.loading').show();
                $(this).find('button').prop('disabled', true);
                
                $.ajax({
                    url: '/api-test/chat-response',
                    type: 'POST',
                    data: {
                        message: message,
                        creative_mode: creativeMode,
                        coding_mode: codingMode
                    },
                    success: function(response) {
                        $('#chatResponse').text(JSON.stringify(response, null, 2));
                    },
                    error: function(xhr) {
                        $('#chatResponse').text('Hata: ' + JSON.stringify(xhr.responseJSON, null, 2));
                    },
                    complete: function() {
                        $('#chatForm').find('.loading').hide();
                        $('#chatForm').find('button').prop('disabled', false);
                    }
                });
            });
        });
    </script>
</body>
</html> 