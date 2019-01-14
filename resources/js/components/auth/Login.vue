<template>
    <form method="POST" action="/login" id="form_login">
        <input type="hidden" name="_token" v-model="csrf" />

        <div class="row clearfix">
            <div class="alert alert-danger" v-show="messages.length > 0">
                <h3>Invalid login</h3>
                <p> {{ messages.join('\r\n') }} </p>
            </div>
            <div class="alert alert-success" v-if="stat != ''">
                {{ stat }}
            </div>
        </div>

        <div class="form-group row">
            <label for="email" class="col-sm-4 col-form-label text-md-right">E-Mail Address</label>

            <div class="col-md-6">
                <input id="email" type="email" class="form-control" name="email" v-model="email" required autofocus>
            </div>
        </div>

        <div class="form-group row">
            <label for="password" class="col-md-4 col-form-label text-md-right">Password</label>

            <div class="col-md-6">
                <input id="password" type="password" class="form-control" name="password" v-model="password" required>
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-6 offset-md-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" v-model="remember_me">

                    <label class="form-check-label" for="remember">
                        Remember Me
                    </label>
                </div>
            </div>
        </div>

        <div class="form-group row mb-0">
            <div class="col-md-8 offset-md-4">
                <button type="button" class="btn btn-primary" @click="submit">
                    Login
                </button>

                <a class="btn btn-link" href="/password/request">Forgot Your Password?</a>
            </div>
        </div>
    </form>
</template>

<script>
    import VueCookie from 'vue-cookie';
    export default {
        components: {
            VueCookie
        },
        props: ['stat'],
        mounted() {
            this.csrf = $('meta[name="csrf-token"]')[0].content;
        },
        data() {
            return {
                messages: [],
                csrf: '',
                remember_me: false,
                email: '',
                password: ''
            }
        },
        methods: {
            submit: function() {
                this.messages = [];
                if (this.email == '' || this.password == '') {
                    this.messages.push('Email and password are required.');
                    return;
                }
                var self = this;
                axios.post('/oauth/token', {
                    username: this.email,
                    password: this.password,
                    client_id: 2,
                    client_secret: 'mO0T6sAr30AWHQkPNzUlwTCS8jdagtGLeSm3Gsmq',
                    grant_type: 'password',
                    scope: ''
                }).then(response => {
                    console.log(response);
                    if(response.data.hasOwnProperty('access_token')) {
                        VueCookie.set('API_ACCESS_TOKEN', response.data.access_token);
                        $('#form_login')[0].submit();
                    }
                }).catch(e => {
                    self.messages.push(e.response.data.message);
                })
            }
        }
    }
</script>

<style scoped>
</style>
