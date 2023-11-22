class Login extends Base {

    constructor() {
        super();
        this.hooks = {};
    }

    /**
     * 绑定登入按钮以及输入框
     * @param {String} submitId 
     * @param {String} usernameId 
     * @param {String} passwordId 
     */
    BindLogin = (submitId = undefined, usernameId = undefined, passwordId = undefined) => {

        const getValue = () => {
            return {
                username: $('#' + usernameId).val(),
                password: $('#' + passwordId).val()
            }
        }

        //绑定方法
        if (this.config.geetest4.CaptchaStatus) {
            this.Geetest4(submitId, (CaptchaData) => {
                const value = getValue();
                this.PostLogin(value.username, value.password, CaptchaData)
            });
        } else {
            $('#' + submitId).click(() => {
                const value = getValue();
                this.PostLogin(value.username, value.password)
            });
        }
    }

    /**
     * 设置PostLogin方法初始、请求成功、请求失败的钩子
     * @param {(Function|undefined)} intiHook 
     * @param {(Function(response)|undefined)} thanHook 
     * @param {(Function(error)|undefined)} catchJHook 
     */
    SetPostLoginHooks = (intiHook, thanHook, catchJHook) => {
        //设置方法
        this.hooks.PostLogin = {};
        this.hooks.PostLogin.inti = intiHook;
        this.hooks.PostLogin.then = thanHook;
        this.hooks.PostLogin.catch = catchJHook;
    }

    /**
     * 发送登入请求
     * @param {String} username 
     * @param {String} password 
     * @param {(Object|undefined)} CaptchaData 
     */
    PostLogin = (username, password, CaptchaData) => {
        this.SetPostLoginHooks(undefined, (response) => {
            //默认回调函数
            this.SetAdminToken(response.data.token);//设置Token
            this.commonFunctions.snackbar('登入成功，正在跳转');
        });

        //是否存在验证参数
        var data;
        if (CaptchaData) { data = CaptchaData; }

        var data = {
            ...data,//合并验证参数
            'userName': username,
            'password': password,
        };
        this.RequestApiUrl('post', 'AuthLogin', 'PostLogin', data);
    }
}