(function(global) {
    'use strict';
 
    //////////////////////////////////////////
    
    var MY_APP = global.MY_APP = global.MY_APP || {};
    
    activate();
    
    function activate() {    
        MY_APP.utils = {
            isVideo : isVideo,
            showWaitModal : showWaitModal
        }        
    }
    
    function showWaitModal(config) {
    
        config = config || {};

        if( config.delay === null || config.delay === undefined ) config.delay = 1000;

        if( config.message && typeof(config.message) != typeof("string")) {
            throw "progresBar may be a string";
        }

        let semaphore = {
            close: false,
            opened: false
          };

        let _updateProgress = (value, progressDiv) => {
            $(progressDiv).css( 'width', value+'%' );
        }

       
        var openModal = function () {
            
           if (semaphore.close) return;

          let document =  global.document;
    
          let divElement = document.createElement( 'div');
          let _divElementStyle =   
                    'display: block;' + 
                    'position: fixed; ' + 
                    'z-index: 1000; ' + 
                    'top: 0; ' + 
                    'left: 0; ' + 
                    'height: 100%;' +  
                    'width: 100%;' + 
                    'background-size: 100px;';

    
          if( !config.message) {
            _divElementStyle += `background: rgba( 255, 255, 255, .8 ) url('data:image/svg+xml;base64, PHN2ZyBjbGFzcz0ibGRzLXNwaW4iIHdpZHRoPSIyMDBweCIgIGhlaWdodD0iMjAwcHgiICB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCIgcHJlc2VydmVBc3BlY3RSYXRpbz0ieE1pZFlNaWQiIHN0eWxlPSJiYWNrZ3JvdW5kOiBub25lOyI+PGcgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoODAsNTApIj4NCjxnIHRyYW5zZm9ybT0icm90YXRlKDApIj4NCjxjaXJjbGUgY3g9IjAiIGN5PSIwIiByPSI3IiBmaWxsPSIjNWE2YjdkIiBmaWxsLW9wYWNpdHk9IjEiIHRyYW5zZm9ybT0ic2NhbGUoMS4wMjc1IDEuMDI3NSkiPg0KICA8YW5pbWF0ZVRyYW5zZm9ybSBhdHRyaWJ1dGVOYW1lPSJ0cmFuc2Zvcm0iIHR5cGU9InNjYWxlIiBiZWdpbj0iLTAuODc1cyIgdmFsdWVzPSIxLjEgMS4xOzEgMSIga2V5VGltZXM9IjA7MSIgZHVyPSIxcyIgcmVwZWF0Q291bnQ9ImluZGVmaW5pdGUiPjwvYW5pbWF0ZVRyYW5zZm9ybT4NCiAgPGFuaW1hdGUgYXR0cmlidXRlTmFtZT0iZmlsbC1vcGFjaXR5IiBrZXlUaW1lcz0iMDsxIiBkdXI9IjFzIiByZXBlYXRDb3VudD0iaW5kZWZpbml0ZSIgdmFsdWVzPSIxOzAiIGJlZ2luPSItMC44NzVzIj48L2FuaW1hdGU+DQo8L2NpcmNsZT4NCjwvZz4NCjwvZz48ZyB0cmFuc2Zvcm09InRyYW5zbGF0ZSg3MS4yMTMyMDM0MzU1OTY0Myw3MS4yMTMyMDM0MzU1OTY0MykiPg0KPGcgdHJhbnNmb3JtPSJyb3RhdGUoNDUpIj4NCjxjaXJjbGUgY3g9IjAiIGN5PSIwIiByPSI3IiBmaWxsPSIjNWE2YjdkIiBmaWxsLW9wYWNpdHk9IjAuODc1IiB0cmFuc2Zvcm09InNjYWxlKDEuMDQgMS4wNCkiPg0KICA8YW5pbWF0ZVRyYW5zZm9ybSBhdHRyaWJ1dGVOYW1lPSJ0cmFuc2Zvcm0iIHR5cGU9InNjYWxlIiBiZWdpbj0iLTAuNzVzIiB2YWx1ZXM9IjEuMSAxLjE7MSAxIiBrZXlUaW1lcz0iMDsxIiBkdXI9IjFzIiByZXBlYXRDb3VudD0iaW5kZWZpbml0ZSI+PC9hbmltYXRlVHJhbnNmb3JtPg0KICA8YW5pbWF0ZSBhdHRyaWJ1dGVOYW1lPSJmaWxsLW9wYWNpdHkiIGtleVRpbWVzPSIwOzEiIGR1cj0iMXMiIHJlcGVhdENvdW50PSJpbmRlZmluaXRlIiB2YWx1ZXM9IjE7MCIgYmVnaW49Ii0wLjc1cyI+PC9hbmltYXRlPg0KPC9jaXJjbGU+DQo8L2c+DQo8L2c+PGcgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNTAsODApIj4NCjxnIHRyYW5zZm9ybT0icm90YXRlKDkwKSI+DQo8Y2lyY2xlIGN4PSIwIiBjeT0iMCIgcj0iNyIgZmlsbD0iIzVhNmI3ZCIgZmlsbC1vcGFjaXR5PSIwLjc1IiB0cmFuc2Zvcm09InNjYWxlKDEuMDUyNSAxLjA1MjUpIj4NCiAgPGFuaW1hdGVUcmFuc2Zvcm0gYXR0cmlidXRlTmFtZT0idHJhbnNmb3JtIiB0eXBlPSJzY2FsZSIgYmVnaW49Ii0wLjYyNXMiIHZhbHVlcz0iMS4xIDEuMTsxIDEiIGtleVRpbWVzPSIwOzEiIGR1cj0iMXMiIHJlcGVhdENvdW50PSJpbmRlZmluaXRlIj48L2FuaW1hdGVUcmFuc2Zvcm0+DQogIDxhbmltYXRlIGF0dHJpYnV0ZU5hbWU9ImZpbGwtb3BhY2l0eSIga2V5VGltZXM9IjA7MSIgZHVyPSIxcyIgcmVwZWF0Q291bnQ9ImluZGVmaW5pdGUiIHZhbHVlcz0iMTswIiBiZWdpbj0iLTAuNjI1cyI+PC9hbmltYXRlPg0KPC9jaXJjbGU+DQo8L2c+DQo8L2c+PGcgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMjguNzg2Nzk2NTY0NDAzNTc3LDcxLjIxMzIwMzQzNTU5NjQzKSI+DQo8ZyB0cmFuc2Zvcm09InJvdGF0ZSgxMzUpIj4NCjxjaXJjbGUgY3g9IjAiIGN5PSIwIiByPSI3IiBmaWxsPSIjNWE2YjdkIiBmaWxsLW9wYWNpdHk9IjAuNjI1IiB0cmFuc2Zvcm09InNjYWxlKDEuMDY1IDEuMDY1KSI+DQogIDxhbmltYXRlVHJhbnNmb3JtIGF0dHJpYnV0ZU5hbWU9InRyYW5zZm9ybSIgdHlwZT0ic2NhbGUiIGJlZ2luPSItMC41cyIgdmFsdWVzPSIxLjEgMS4xOzEgMSIga2V5VGltZXM9IjA7MSIgZHVyPSIxcyIgcmVwZWF0Q291bnQ9ImluZGVmaW5pdGUiPjwvYW5pbWF0ZVRyYW5zZm9ybT4NCiAgPGFuaW1hdGUgYXR0cmlidXRlTmFtZT0iZmlsbC1vcGFjaXR5IiBrZXlUaW1lcz0iMDsxIiBkdXI9IjFzIiByZXBlYXRDb3VudD0iaW5kZWZpbml0ZSIgdmFsdWVzPSIxOzAiIGJlZ2luPSItMC41cyI+PC9hbmltYXRlPg0KPC9jaXJjbGU+DQo8L2c+DQo8L2c+PGcgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMjAsNTAuMDAwMDAwMDAwMDAwMDEpIj4NCjxnIHRyYW5zZm9ybT0icm90YXRlKDE4MCkiPg0KPGNpcmNsZSBjeD0iMCIgY3k9IjAiIHI9IjciIGZpbGw9IiM1YTZiN2QiIGZpbGwtb3BhY2l0eT0iMC41IiB0cmFuc2Zvcm09InNjYWxlKDEuMDc3NSAxLjA3NzUpIj4NCiAgPGFuaW1hdGVUcmFuc2Zvcm0gYXR0cmlidXRlTmFtZT0idHJhbnNmb3JtIiB0eXBlPSJzY2FsZSIgYmVnaW49Ii0wLjM3NXMiIHZhbHVlcz0iMS4xIDEuMTsxIDEiIGtleVRpbWVzPSIwOzEiIGR1cj0iMXMiIHJlcGVhdENvdW50PSJpbmRlZmluaXRlIj48L2FuaW1hdGVUcmFuc2Zvcm0+DQogIDxhbmltYXRlIGF0dHJpYnV0ZU5hbWU9ImZpbGwtb3BhY2l0eSIga2V5VGltZXM9IjA7MSIgZHVyPSIxcyIgcmVwZWF0Q291bnQ9ImluZGVmaW5pdGUiIHZhbHVlcz0iMTswIiBiZWdpbj0iLTAuMzc1cyI+PC9hbmltYXRlPg0KPC9jaXJjbGU+DQo8L2c+DQo8L2c+PGcgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMjguNzg2Nzk2NTY0NDAzNTcsMjguNzg2Nzk2NTY0NDAzNTc3KSI+DQo8ZyB0cmFuc2Zvcm09InJvdGF0ZSgyMjUpIj4NCjxjaXJjbGUgY3g9IjAiIGN5PSIwIiByPSI3IiBmaWxsPSIjNWE2YjdkIiBmaWxsLW9wYWNpdHk9IjAuMzc1IiB0cmFuc2Zvcm09InNjYWxlKDEuMDkgMS4wOSkiPg0KICA8YW5pbWF0ZVRyYW5zZm9ybSBhdHRyaWJ1dGVOYW1lPSJ0cmFuc2Zvcm0iIHR5cGU9InNjYWxlIiBiZWdpbj0iLTAuMjVzIiB2YWx1ZXM9IjEuMSAxLjE7MSAxIiBrZXlUaW1lcz0iMDsxIiBkdXI9IjFzIiByZXBlYXRDb3VudD0iaW5kZWZpbml0ZSI+PC9hbmltYXRlVHJhbnNmb3JtPg0KICA8YW5pbWF0ZSBhdHRyaWJ1dGVOYW1lPSJmaWxsLW9wYWNpdHkiIGtleVRpbWVzPSIwOzEiIGR1cj0iMXMiIHJlcGVhdENvdW50PSJpbmRlZmluaXRlIiB2YWx1ZXM9IjE7MCIgYmVnaW49Ii0wLjI1cyI+PC9hbmltYXRlPg0KPC9jaXJjbGU+DQo8L2c+DQo8L2c+PGcgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNDkuOTk5OTk5OTk5OTk5OTksMjApIj4NCjxnIHRyYW5zZm9ybT0icm90YXRlKDI3MCkiPg0KPGNpcmNsZSBjeD0iMCIgY3k9IjAiIHI9IjciIGZpbGw9IiM1YTZiN2QiIGZpbGwtb3BhY2l0eT0iMC4yNSIgdHJhbnNmb3JtPSJzY2FsZSgxLjAwMjUgMS4wMDI1KSI+DQogIDxhbmltYXRlVHJhbnNmb3JtIGF0dHJpYnV0ZU5hbWU9InRyYW5zZm9ybSIgdHlwZT0ic2NhbGUiIGJlZ2luPSItMC4xMjVzIiB2YWx1ZXM9IjEuMSAxLjE7MSAxIiBrZXlUaW1lcz0iMDsxIiBkdXI9IjFzIiByZXBlYXRDb3VudD0iaW5kZWZpbml0ZSI+PC9hbmltYXRlVHJhbnNmb3JtPg0KICA8YW5pbWF0ZSBhdHRyaWJ1dGVOYW1lPSJmaWxsLW9wYWNpdHkiIGtleVRpbWVzPSIwOzEiIGR1cj0iMXMiIHJlcGVhdENvdW50PSJpbmRlZmluaXRlIiB2YWx1ZXM9IjE7MCIgYmVnaW49Ii0wLjEyNXMiPjwvYW5pbWF0ZT4NCjwvY2lyY2xlPg0KPC9nPg0KPC9nPjxnIHRyYW5zZm9ybT0idHJhbnNsYXRlKDcxLjIxMzIwMzQzNTU5NjQzLDI4Ljc4Njc5NjU2NDQwMzU3KSI+DQo8ZyB0cmFuc2Zvcm09InJvdGF0ZSgzMTUpIj4NCjxjaXJjbGUgY3g9IjAiIGN5PSIwIiByPSI3IiBmaWxsPSIjNWE2YjdkIiBmaWxsLW9wYWNpdHk9IjAuMTI1IiB0cmFuc2Zvcm09InNjYWxlKDEuMDE1IDEuMDE1KSI+DQogIDxhbmltYXRlVHJhbnNmb3JtIGF0dHJpYnV0ZU5hbWU9InRyYW5zZm9ybSIgdHlwZT0ic2NhbGUiIGJlZ2luPSIwcyIgdmFsdWVzPSIxLjEgMS4xOzEgMSIga2V5VGltZXM9IjA7MSIgZHVyPSIxcyIgcmVwZWF0Q291bnQ9ImluZGVmaW5pdGUiPjwvYW5pbWF0ZVRyYW5zZm9ybT4NCiAgPGFuaW1hdGUgYXR0cmlidXRlTmFtZT0iZmlsbC1vcGFjaXR5IiBrZXlUaW1lcz0iMDsxIiBkdXI9IjFzIiByZXBlYXRDb3VudD0iaW5kZWZpbml0ZSIgdmFsdWVzPSIxOzAiIGJlZ2luPSIwcyI+PC9hbmltYXRlPg0KPC9jaXJjbGU+DQo8L2c+DQo8L2c+PC9zdmc+') 50% 50% no-repeat;`
          }
          else {

            _divElementStyle += `background: rgba(0, 0, 0, 0.6);`;
    
            let progress = document.createElement( 'div');
            progress.style = 
                'padding-right: 15px;'+
                'padding-left: 15px;'+
                'width: 80%;'+
                'max-width: 800px;'+
                'background-color: #fff;'+ 
                'margin: 0 auto;'+ 
                'flex-direction: column;'+ 
                'justify-content: center;'+ 
                'height: 10.3em;'+ 
                'margin-top: 20em;'+ 
                'border-radius: 0;';
            progress.className = 'progress';
            divElement.appendChild(progress);
    
            let divText = document.createElement("div");
            divText.style = 
                'color: #5a6b7d;'+
                'white-space: nowrap;'+
                'overflow: hidden;'+
                'margin-left: 1.5em;'+
                'margin-right: 1.5em;'+
                'margin-top: 1em;';
                
            progress.appendChild(divText);

            let h3 = document.createElement("h3");
            h3.style = `margin-bottom: 0;`;
            h3.appendChild(document.createTextNode(config.message));
            divText.appendChild(h3);            
    
            let hr =  document.createElement("hr");
            hr.style = `width: 100%;`;
            progress.appendChild(hr);
    
            let progressBar = document.createElement("div");
            progressBar.className = `progress-bar progress-bar-striped progress-bar-animated`;
            progressBar.style = 
                'height: 2.5em;'+
               // 'margin-left: 1.5em;'+
                //'margin-right: 1.5em;'+
                'margin-bottom: 1em;'+
                'background-color: #b1acac;'; 
            progress.appendChild(progressBar);

            if( config.progress != null) {
                _updateProgress(config.progress, progressBar)
            }
            semaphore.progressDiv = progressBar;
            
          }
          
          divElement.style = _divElementStyle;

          semaphore._parent = document.getElementsByTagName('body')[0]
          semaphore._parent.appendChild(divElement);
          semaphore._element = divElement;
          semaphore.opened = true;
    
        }
       
        if( config.delay == 0 ) openModal();
        else setTimeout(openModal, config.delay);
    
        return {
            close: function () {
                semaphore.close = true;
                if (semaphore.opened ) {
                    semaphore._parent.removeChild(semaphore._element);
                }
            },
            setProgress: function(value){
                if( !semaphore.progressDiv ) return;
                _updateProgress(value, semaphore.progressDiv);
            }
        }

        
    
    }
  
    function isVideo(name) {

        if( !name )  return false;

        var extTypes = [
            "3gp", "asf", "asx","avi", "flv", "m4v", "mng", "mov", 
            "mp4", "mp4v", "mpeg", "mpg", "qt" , "wmv", "wmx", "webm", "mkv"
        ]

        var i = name.lastIndexOf('.');
        const ext =  (i < 0) ? '' : name.substr(i+1);
        
        for (let index = 0; index < extTypes.length; index++) {
            const _ext = extTypes[index];
            if( _ext == ext.toLowerCase() ) {
                return true;
            }            
        }
        return false;
    }
    
    
    function _guid() {
        function s4() {
            return Math.floor((1 + Math.random()) * 0x10000)
              .toString(16)
              .substring(1);
        }
        return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
          s4() + '-' + s4() + s4() + s4();
    }
    
    function _readCookie(name) {
    
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for(var i=0;i < ca.length;i++) {
            var c = ca[i];
            while (c.charAt(0) === ' ') {
                c = c.substring(1,c.length);
            }
            if (c.indexOf(nameEQ) === 0) {
                return c.substring(nameEQ.length,c.length);
            }
        }
        return null;
    
    }
    
    
    })(window);    