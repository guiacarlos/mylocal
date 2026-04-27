(function (global) {
    'use strict';

    var EP = '/gateway.php';

    function getToken() {
        return localStorage.getItem('acide_token') || '';
    }

    function buildHeaders(json) {
        var h = {};
        var t = getToken();
        if (t) h['Authorization'] = 'Bearer ' + t;
        if (json) h['Content-Type'] = 'application/json';
        return h;
    }

    function handleUnauthorized(status) {
        if (status === 401) {
            localStorage.removeItem('acide_token');
            localStorage.removeItem('acide_user');
            if (window.location.pathname.indexOf('/login') === -1) {
                window.location.href = '/login';
            }
        }
    }

    async function call(action, data) {
        var body = JSON.stringify({ action: action, data: data || {} });
        try {
            var res = await fetch(EP, {
                method: 'POST',
                headers: buildHeaders(true),
                credentials: 'include',
                body: body
            });
            handleUnauthorized(res.status);
            var json = await res.json();
            if (json.success === false || json.status === 'error') {
                return { success: false, error: json.error || json.message || 'Error del servidor' };
            }
            return { success: true, data: json.data !== undefined ? json.data : json };
        } catch (err) {
            return { success: false, error: err.message || 'Error de conexion' };
        }
    }

    async function get(collection, id) {
        return call('read', { collection: collection, id: id });
    }

    async function update(collection, id, data) {
        return call('update', { collection: collection, id: id, data: data });
    }

    global.mylocalService = { call: call, get: get, update: update, getToken: getToken };

})(window);
