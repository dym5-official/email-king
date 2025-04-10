import axios from "axios";

const query = (params) => {
    params._wpnonce = window.VARS._wpnonce;

    return Object.keys(params).map((key) => {
        return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
    }).join('&');
}

const url = (endpoint) => {
    let params = query(endpoint[2] || {});
    let apiurl = `${window.VARS.ajaxurl}?action=email-king&__scope__=${endpoint[0]}&__key__=${endpoint[1]}&${params}`;

    return apiurl;
}

const get = (endpoint) => {
    return axios.get(url(endpoint));
}

const post = (endpoint, data) => {
    return axios.post(url(endpoint), data);
}

const api = {
    get,
    post,
    url,
};

export default api;