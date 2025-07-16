(function($){
    $(document).ready(function(){
        var $prompt   = $('#aicg-prompt');
        var $generate = $('#aicg-generate-button');
        var $save     = $('#aicg-save-button');
        var $preview  = $('#aicg-preview');
        var $textModelSelect  = $('#inputModeloTexto');
        var $imageModelSelect = $('#inputModeloImagem');
        var $numImagesInput   = $('#inputNumeroImagens');

        // Fallback de mensagens caso aicgData.i18n não esteja definido
        var msg = (window.aicgData && window.aicgData.i18n) ? aicgData.i18n : {
            promptRequired:     'Por favor, digite um prompt.',
            generating:         'Gerando...',
            generatingArticle:  'Gerando artigo...',
            generateArticle:    'Gerar Artigo',
            errorGenerate:      'Erro ao gerar conteúdo.',
            saving:             'Salvando...',
            saved:              'Salvo',
            saveArticle:        'Salvar Artigo',
            errorSave:          'Erro ao salvar post.',
            editPostUrl:        window.location.origin + '/wp-admin/post.php?post='
        };

        // URL para regenerar imagens, com fallback
        var regenerateUrl = (window.aicgData && aicgData.regenerateImageUrl)
            ? aicgData.regenerateImageUrl
            : (aicgData.generateUrl.replace(/\/generate$/, '/regenerate-image'));

        // Função para resetar botões ao estado inicial
        function resetGenerateButton() {
            $generate.prop('disabled', false).text(msg.generateArticle);
        }

        // Gerar Artigo
        $generate.on('click', function(e){
            e.preventDefault();

            var promptText = $prompt.val().trim();
            if (!promptText) {
                alert(msg.promptRequired);
                return;
            }

            var textModel  = $textModelSelect.val();
            var imageModel = $imageModelSelect.val();
            var numImages = parseInt( $numImagesInput.val(), 10 ) || 1;

            $generate.prop('disabled', true).text(msg.generating);
            $preview.html('<p>' + msg.generatingArticle + '</p>');

            $.ajax({
                url: aicgData.generateUrl,
                method: 'POST',
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify({
                    prompt: promptText,
                    text_model: textModel,
                    image_model: imageModel,
                    num_images: numImages
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
                                'style="position:absolute; top:5px; right:5px; display:none;">Regenerar</button>';
                        html += '</div>';
                    });
                }

                html += response.content || '';
                $preview.html(html);
                $save.prop('disabled', false);
                // Armazena dados para salvar
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

        // Salvar Artigo
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

        // Mostrar o botão ao passar o mouse
        $preview
          .on('mouseenter', '.aicg-image-wrapper', function(){
            $(this).find('.aicg-regenerate-button').show();
          })
          .on('mouseleave', '.aicg-image-wrapper', function(){
            $(this).find('.aicg-regenerate-button').hide();
          });

        // Click em "Regenerar"
        $preview.on('click', '.aicg-regenerate-button', function(){
          var $btn = $(this);
          var $wrapper = $btn.closest('.aicg-image-wrapper');
          var $img = $wrapper.find('img');
          var alt = $img.attr('alt') || '';

          var idx = parseInt( $wrapper.data('idx'), 10 );
          var previewData = $preview.data('aicgGenerated') || {};

          $btn.hide();
          $img.hide();
          var $txt = $('<div class="text-muted">Regenerando...</div>');
          $wrapper.append($txt);

          $.ajax({
            url: regenerateUrl,
            method: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({ prompt: alt }),
            beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', aicgData.nonce); }
          })
          .done(function(resp){
            if (resp.url) {
              $img.attr('src', resp.url).show();
              // Atualiza o array de imagens no objeto de previewData
              if ( Array.isArray(previewData.images) && previewData.images[idx] ) {
                  previewData.images[idx].url = resp.url;
                  $preview.data('aicgGenerated', previewData);
              }
            }
          })
          .fail(function(){
            alert('Falha ao regenerar imagem.');
          })
          .always(function(){
            $txt.remove();
            $btn.show();
          });
        });
    });
})(jQuery);