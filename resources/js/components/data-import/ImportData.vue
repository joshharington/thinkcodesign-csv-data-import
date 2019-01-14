<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card mb-0 pb-0">
                    <div class="card-header">Import Data</div>

                    <div class="card-body">
                        <div class="row" v-if="has_error">
                            <div class="col-md-12">
                                <div class="alert alert-danger">
                                    <p class="pb-0 mb-0">{{ error_message }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="row" v-if="process_success">
                            <div class="col-md-12">
                                <div class="alert alert-success">
                                    <p class="pb-0 mb-0">{{ message }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="row" v-if="!process_success">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Zip file</label>
                                    <vue-dropzone ref="myVueDropzone" id="dropzone" :options="dropzoneOptions"></vue-dropzone>
                                </div>
                            </div>
                        </div>

                        <div class="row" v-if="!process_success">
                            <div class="col-md-12">
                                <div class="form-group mb-0 pb-0">
                                    <button type="button" class="btn btn-primary btn-block" @click="upload">Upload</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import vue2Dropzone from 'vue2-dropzone'
    import 'vue2-dropzone/dist/vue2Dropzone.min.css'

    export default {
        name: "ImportData",
        components: {
            vueDropzone: vue2Dropzone
        },
        data: function () {
            return {
                dropzoneOptions: {
                    url: '/data-import/upload',
                    thumbnailWidth: 150,
                    maxFilesize: 64,
                    autoProcessQueue: false
                },
                has_error: false,
                error_message: 'Error',
                message: '',
                process_success: false,
            }
        },
        mounted() {
          var csrf = $('meta[name="csrf-token"]')[0].content;
          this.dropzoneOptions.headers = { '_token': csrf, 'Content-Type': 'application/zip' }
        },
        methods: {
            upload: function() {
                // this.$refs.myVueDropzone.processQueue();
                var files = this.$refs.myVueDropzone.getQueuedFiles();
                console.log('files', files);

                this.messages = [];
                if (this.email == '' || this.password == '') {
                    this.messages.push('Email and password are required.');
                    return;
                }
                var self = this;
                const formData = new FormData();
                formData.append('files', files[0]);
                axios.post('/data-import/upload', formData, {headers: { 'Content-Type' : 'multipart/form-data'} }).then(response => {
                    console.log(response);
                    if(response.data.hasOwnProperty('error')) {
                        if(response.data.error == "true") {
                            self.has_error = true;
                            self.error_message = response.data.message;
                            this.$refs.myVueDropzone.removeAllFiles();
                        }
                        if(response.data.error == "false") {
                            self.process_success = true;
                            self.has_error = false;
                            self.message = response.data.message;
                            this.$refs.myVueDropzone.removeAllFiles();
                        }
                    }
                }).catch(e => {
                    console.log('e', e);
                    // self.messages.push(e.response.data.message);
                })

            }
        }
    }
</script>

<style scoped>
    .dropzone {
        min-height: 1px !important;
    }
</style>
