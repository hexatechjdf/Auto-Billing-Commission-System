(() => {
    let isLocationChanged = false;
    function locationChanged() {
        if (!isLocationChanged) {
            return;
        }
        isLocationChanged = false;
        setTimeout(async () => {
            let SSO = await getSSOToken();
            document.querySelectorAll('iframe').forEach(x => {
                sendSSO(x.contentWindow, SSO);
            });
        }, 3000);
    }
    async function getSSOToken() {
        try {
            ssoInfo = await window.exposeSessionDetails('673b831895e1f4267f22a692');
        } catch (error) {
            ssoInfo = null;
        }
        return ssoInfo;
    }

    window.addEventListener('message', function (e) {

        let data = e.data;
        if (typeof data == 'object') {
            if (['REQUEST_USER_DATA', 'BUY_REQUEST_USER_DATA'].includes(data.message ?? "")) {
                (async () => {
                    let SSO = await getSSOToken();
                    sendSSO(e.source, SSO);
                })();
            }
        }
    });

    function sendSSO(frame = null,SSO) {
        if (frame) {
            frame.postMessage({
                message: 'REQUEST_USER_DATA_RESPONSE',
                payload: SSO,
            }, '*');
        }
    }

    window.addEventListener("locationChangeEvent", async function (e) {
        isLocationChanged = true;
    });
    window.addEventListener("routeChangeEvent", function () {
        locationChanged();
    });
})()
