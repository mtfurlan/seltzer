window.onload = function () {
    var iframe = document.getElementById('makepi-iframe-ui');
    var token = getCookieValue('makepi-token');

    if (!iframe) {
        return console.log('Iframe not found');
    }

    if (!token) {
        return console.log('No Token');
    }

    iframe.contentWindow.postMessage({ token: token, route: 'membership-info' }, '*');

    function getCookieValue(a) {
        var b = document.cookie.match('(^|[^;]+)\\s*' + a + '\\s*=\\s*([^;]+)');
        return b ? b.pop() : '';
    }
};
