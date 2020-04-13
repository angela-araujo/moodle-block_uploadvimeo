(function(global) {
    'use strict';
    
    let MY_APP = global.MY_APP = global.MY_APP || {};
    
    activate();
    
    function activate() {    
        MY_APP.sendFile = sendFile;   
    }

    function sendFile(files, accesstoken){

      const file = files[0];

      if( !MY_APP.utils.isVideo(file.name)) {
        Swal.fire({
          heightAuto: false,
          text: `Houve um problema com seu arquivo. Talvez ele não seja um arquivo 
            de vídeo ou ele usa um codec que não aceitamos!`,
          icon: 'warning',                      
        });
        return;
      }
      
       Swal.fire({
            heightAuto: false,
            showCancelButton: true,
            title: "Enviar vídeo",
            html:
              '<input id="swal-input1" placeholder="Título do vídeo" autofocus class="swal2-input" style="min-width: 100%">' +
              '<textarea rows="8" id="swal-input2" placeholder="Descrição do vídeo" class="swal2-input" ' +
                'style="min-width: 100%;margin-top: 0px; height: 100px">',
            preConfirm: () => {
              let title = document.getElementById('swal-input1').value;
              let description = document.getElementById('swal-input2').value;     
              if( !title || !description ) {                
                  Swal.showValidationMessage(
                    'Obrigatório o preenchimento do título e da descrição'
                  )
                  return false;
              }              
              return new Promise( (resolve, reject) => {              
                resolve({
                  'title':title, 
                  'description':description
                })
              })
            },
            onOpen:  () => {        
            }
          }).then( (result) => {
              if( result.dismiss && result.dismiss == 'cancel') return;
              let wait = MY_APP.utils.showWaitModal( { message: "Enviando vídeo (pode demorar)...", delay: 0, progress: 0});
              send(0, wait, result.value);            
          }).catch(swal.noop)


          let send = (time, wait, parameter) => {
            ;(new VimeoUpload({
              name: parameter.title,
              description: parameter.description,
              //extraOpts: MY_APP.config.vimeOpts,
			  extraOpts: {},
			  file: file,
              //token: MY_APP.config.token,
			  token: accesstoken,
              onError: function(data) {
                wait.close();
                Swal.fire({
                  heightAuto: false,
                  title: "Oops..",
                  text: `Ocorreu um erro inesperado. Verifique a sua conexão de Internet e tente novamente.`,
                  icon: 'error',                      
                })
              },
              onProgress: function(data) {
                const total = (data.loaded / data.total) * 100;
                if( total > 100 ) total = 100;
                wait.setProgress( total );

              },
              onComplete: function(videoId, metadata) {
                wait.close();

                //var url = 'https://vimeo.com/' + videoId;
				
				var whitelistDomains = ['ead.puc-rio.br'];
                //for (let index = 0; index < MY_APP.config.whitelistDomains.length; index++) {
				for (let index = 0; index < whitelistDomains.length; index++) {
                  //const domain = MY_APP.config.whitelistDomains[index];
				  const domain = whitelistDomains[index];
                  this.addDomain(videoId, domain);   
                }                

                $("#success_alert").show();
                //$("#success_alert > pre").text(metadata.embed.html);

                var urlform = new URL(location.href);
                var courseid = urlform.searchParams.get("courseid");                
                var urlredirect = location.origin + location.pathname + '?courseid=' + courseid + '&urivideo=' + metadata.uri;
                setTimeout(function(){$(location).attr('href', urlredirect);}, 3000);

              }
            })).upload();         
          }

    }


})(window);    