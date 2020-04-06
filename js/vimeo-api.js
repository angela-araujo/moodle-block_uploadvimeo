;
(function(root, factory) {
    root.VimeoUpload = factory()
}(this, function() {
    var defaults = {
        api_url: 'https://api.vimeo.com',
        name: 'Nome Padrão',
        description: 'Descrição Padrão',
        contentType: 'application/offset+octet-stream',
        api_version: '3.4',
        token: null,
        file: {},  
        onComplete: function() {},
        onProgress: function() {},
        onError: function() {}
    }

    /**
     * @constructor
     * @param {object} options Mapa de ocpoes
     * @param {string} options.token Token de acesso
     * @param {blob} options.file Arquivo para upload
     * @param {string} [options.fileId] Id do arquivo para envio
     * @param {object} [options.params] parametros de url adicionais
     * @param {object} [options.extraOpts] parametros opcionais adicionais
     * @param {object} [options.metadata] File metadata
     * @param {function} [options.onComplete] Callback para qunado terminar o envio
     * @param {function} [options.onProgress] Callback para controle do progresso
     * @param {function} [options.onError] Callback se o upload falhar
     */
    var me = function(opts) {
        
        for (var i in defaults) {
            this[i] = (opts[i] !== undefined) ? opts[i] : defaults[i];
        }
        this.accept = 'application/vnd.vimeo.*+json;version=' + this.api_version

        this.httpMethod = opts.fileId ? 'PUT' : 'POST'

        this.videoData = {
            name: (opts.name > '') ? opts.name : defaults.name,
            description: (opts.description > '') ? opts.description : defaults.description,
        }
        if( opts.extraOpts ) {
            this.videoData = Object.assign({}, this.videoData,  opts.extraOpts);    
        }    

        if (!(this.url = opts.url)) {
            var params = opts.params || {} 
            this.url = this.buildUrl_(opts.fileId, params, opts.baseUrl)
        }
    }


    me.prototype.defaults = function(opts) {
        return defaults;
    }

    me.prototype.upload = function() {
        var xhr = new XMLHttpRequest()
        xhr.open(this.httpMethod, this.url, true)
        if (this.token) {
          xhr.setRequestHeader('Authorization', 'Bearer ' + this.token)
        }
        xhr.setRequestHeader('Content-Type', 'application/json')
        xhr.setRequestHeader('Accept', this.accept)

        xhr.onload = function(e) {
            if (e.target.status < 400) {
                var response = JSON.parse(e.target.responseText)
                this.url = response.upload.upload_link
                this.video_url = response.uri
                this.user = response.user
                this.serverMetadata = response;
                this.ticket_id = response.ticket_id
                this.complete_url = defaults.api_url + response.complete_uri
                this.sendFile_()
            } else {
                this.onUploadError_(e)
            }
        }.bind(this)

        xhr.onerror = this.onUploadError_.bind(this)
        const body = this.videoData
        body.upload = {
            approach: 'tus',
            size: this.file.size
        }
        xhr.send(JSON.stringify(body))
    }

    me.prototype.addDomain = function(fileId, domain, onComplete, onError) {
        if( !domain ) throw "Domain is required"
        if( !fileId ) throw "FileId is required"
        var xhr = new XMLHttpRequest()
        xhr.open("PUT", `https://api.vimeo.com/videos/${fileId}/privacy/domains/${domain}`, true)
        if (this.token) {
          xhr.setRequestHeader('Authorization', 'Bearer ' + this.token)
        }
        xhr.onload = function(e) {

            // get vimeo upload  url, user (for available quote), ticket id and complete url
            if (e.target.status < 400) {
                if( onComplete ) onComplete();
            } else {
                if( onError ) onError();
            }
        }.bind(this);        
        xhr.send()
    }

    me.prototype.onUploadError_ = function(e) {
        this.onError(e.target.response); 
    }
    

    me.prototype.sendFile_ = function() {

        self = this;
        var options = {            
            chunkSize: 5000000,
            endpoint: self.url,
            uploadUrl: self.url,
            resume: true,
            autoRetry: true,
            retryDelays: [0, 1000, 3000, 5000],
            onError: function (error) {
                this.onError(error);
            },
            onProgress: function (bytesUploaded, bytesTotal) {
                self.onProgress({
                    loaded: bytesUploaded,
                    total: bytesTotal
                })
            },
            onSuccess: function (resp) {
                self.complete_();               
            }
        };
        upload = new tus.Upload(self.file, options);
        upload.start();
    }


    me.prototype.complete_ = function(xhr) {
        const video_id = this.video_url.split('/').pop()
        this.onComplete(video_id, this.serverMetadata);
    }



    me.prototype.buildQuery_ = function(params) {
        params = params || {}
        return Object.keys(params).map(function(key) {
            return encodeURIComponent(key) + '=' + encodeURIComponent(params[key])
        }).join('&')
    }

    me.prototype.buildUrl_ = function(id, params, baseUrl) {
        var url = baseUrl || defaults.api_url + '/me/videos'
        if (id) {
            url += id
        }
        var query = this.buildQuery_(params)
        if (query) {
            url += '?' + query
        }
        return url
    }

    return me
}))