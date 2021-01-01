export default {
    methods: {
        axios_handleError (error) {
            let errorMessage = ''
            if (error.response) {
                // Request made and server responded
                console.log(error.response.data);
                console.log(error.response.status);
                console.log(error.response.headers);
                let statusCode = parseInt(error.response.status)
                if (statusCode === 401) {
                    errorMessage = 'ابتدا وارد شوید.'
                } else if (statusCode >499 && statusCode < 600) {
                    errorMessage = 'یک مشکل فنی رخ داده است. لطفا متن زیر را برای پشتیبانی ارسال کنید.' +
                        '<br>' +
                        error.response.data.message
                } else {
                    errorMessage = error.message
                }
            } else if (error.request) {
                // The request was made but no response was received
                console.log(error.request);
                errorMessage = 'مشکلی رخ داده است. مجدد تلاش کنید.'
            } else {
                // Something happened in setting up the request that triggered an Error
                console.log('Error', error.message);
                errorMessage = error.message
            }

            this.$store.dispatch('alerts/fire', {
                icon: 'error',
                title: 'توجه',
                message: errorMessage
            });
        }
    }
};
