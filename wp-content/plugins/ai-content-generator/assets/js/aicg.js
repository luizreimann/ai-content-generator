(function($){
    $(document).ready(function(){
        var $prompt           = $('#aicg-prompt');
        var $generate         = $('#aicg-generate-button');
        var $save             = $('#aicg-save-button');
        var $preview          = $('#aicg-preview');
        var $textModelSelect  = $('#inputModeloTexto');
        var $imageModelSelect = $('#inputModeloImagem');
        var $numImagesInput   = $('#inputNumeroImagens');
        var $minWordsInput    = $('#inputNumeroPalavras');
        var $apiKeyInput      = $('#inputApiKey');

        // Fallback messages if aicgData.i18n is not defined
        var msg = (window.aicgData && window.aicgData.i18n) ? aicgData.i18n : {
            promptRequired:     'Please enter a prompt.',
            generating:         'Generating...',
            generatingArticle:  'Generating article...',
            generateArticle:    'Generate Article',
            errorGenerate:      'Error generating content.',
            saving:             'Saving...',
            saved:              'Saved',
            saveArticle:        'Save Article',
            errorSave:          'Error saving post.',
            editPostUrl:        window.location.origin + '/wp-admin/post.php?post='
        };

        // URL for regenerating images, with fallback
        var regenerateUrl = (window.aicgData && aicgData.regenerateImageUrl)
            ? aicgData.regenerateImageUrl
            : aicgData.generateUrl.replace(/\/generate$/, '/regenerate-image');

        // URL to save API key, with fallback
        var saveApiKeyUrl = (window.aicgData && aicgData.saveApiKeyUrl)
            ? aicgData.saveApiKeyUrl
            : aicgData.generateUrl.replace(/\/generate$/, '/save-api-key');

        // Save API Key on blur
        $apiKeyInput.on('blur', function() {
            var key = $(this).val().trim();
            if (!key) {
                return;
            }
            $.ajax({
                url: saveApiKeyUrl,
                method: 'POST',
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify({ api_key: key }),
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', aicgData.nonce);
                }
            })
            .done(function() {
                console.log('API key saved successfully.');
            })
            .fail(function() {
                console.error('Failed to save API key.');
            });
        });

        // Function to reset the generate button to its initial state
        function resetGenerateButton() {
            $generate.prop('disabled', false).text(msg.generateArticle);
        }

        // Generate Article
        $generate.on('click', function(e){
            e.preventDefault();

            var promptText = $prompt.val().trim();
            if (!promptText) {
                alert(msg.promptRequired);
                return;
            }

            var textModel  = $textModelSelect.val();
            var imageModel = $imageModelSelect.val();
            var numImages  = parseInt( $numImagesInput.val(), 10 ) || 1;
            var minWords   = parseInt( $minWordsInput.val(), 10 ) || 0;
            var apiKey     = $apiKeyInput.val().trim();

            $generate.prop('disabled', true).text(msg.generating);
            $preview.html('<p>' + msg.generatingArticle + '</p>');

            $.ajax({
                url: aicgData.generateUrl,
                method: 'POST',
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify({
                    prompt:      promptText,
                    text_model:  textModel,
                    image_model: imageModel,
                    num_images:  numImages,
                    min_words:   minWords,
                    api_key:     apiKey
                }),
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', aicgData.nonce);
                }
            })
            .done(function(response){
                var html = '';
                html += '<h2>' + (response.title || '') + '</h2>';
                html += '<p><em>' + (response.excerpt || '') + '</em></p>';

                if (Array.isArray(response.images)) {
                    response.images.forEach(function(img, idx){
                        var url = img.url || '';
                        var alt = img.alt || '';
                        html += '<div class="aicg-image-wrapper position-relative d-inline-block mb-3" data-idx="' + idx + '">';
                        html +=   '<img src="' + url + '" alt="' + alt + '" class="img-fluid" />';
                        html +=   '<button type="button" class="btn btn-sm btn-secondary aicg-regenerate-button" ' +
                                'style="position:absolute; top:5px; right:5px; display:none;">Regenerate</button>';
                        html += '</div>';
                    });
                }

                html += response.content || '';
                $preview.html(html);
                $save.prop('disabled', false);
                $preview.data('aicgGenerated', response);
            })
            .fail(function(xhr){
                var err = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : msg.errorGenerate;
                $preview.html('<p class="text-danger">' + err + '</p>');
            })
            .always(function(){
                resetGenerateButton();
            });
        });

        // Save Article
        $save.on('click', function(e){
            e.preventDefault();

            var data = $preview.data('aicgGenerated');
            if (!data) { return; }

            $save.prop('disabled', true).text(msg.saving);

            $.ajax({
                url: aicgData.saveUrl,
                method: 'POST',
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify(data),
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', aicgData.nonce);
                }
            })
            .done(function(response){
                if (response.post_id) {
                    window.location.href = msg.editPostUrl + response.post_id + '&action=edit';
                } else {
                    $save.text(msg.saved);
                }
            })
            .fail(function(xhr){
                var err = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : msg.errorSave;
                $preview.append('<p class="text-danger">' + err + '</p>');
                $save.prop('disabled', false).text(msg.saveArticle);
            });
        });

        // Show the regenerate button on hover
        $preview
          .on('mouseenter', '.aicg-image-wrapper', function(){
            $(this).find('.aicg-regenerate-button').show();
          })
          .on('mouseleave', '.aicg-image-wrapper', function(){
            $(this).find('.aicg-regenerate-button').hide();
          });

        // Click on "Regenerate"
        $preview.on('click', '.aicg-regenerate-button', function(){
          var $btn        = $(this);
          var $wrapper    = $btn.closest('.aicg-image-wrapper');
          var $img        = $wrapper.find('img');
          var alt         = $img.attr('alt') || '';
          var apiKey      = $apiKeyInput.val().trim();
          var idx         = parseInt( $wrapper.data('idx'), 10 );
          var previewData = $preview.data('aicgGenerated') || {};

          $btn.hide();
          $img.hide();
          var $txt = $('<div class="text-muted">Gerando novamente...</div>');
          $wrapper.append($txt);

          $.ajax({
            url: regenerateUrl,
            method: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({ prompt: alt, api_key: apiKey }),
            beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', aicgData.nonce); }
          })
          .done(function(resp){
            if (resp.url) {
              $img.attr('src', resp.url).show();
              if ( Array.isArray(previewData.images) && previewData.images[idx] ) {
                  previewData.images[idx].url = resp.url;
                  $preview.data('aicgGenerated', previewData);
              }
            }
          })
          .fail(function(){
            alert('Falha ao gerar imagem.');
          })
          .always(function(){
            $txt.remove();
            $btn.show();
          });
        });
    });
})(jQuery);