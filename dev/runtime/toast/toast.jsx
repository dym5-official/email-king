import VanillaToasts from "vanillatoasts";
import { getMessage } from "../ui/status/status";

import "./toast.css";

const showToast = (title, type, conf) => {
    VanillaToasts.create({
        title,
        type,
        positionClass: 'bottomCenter',
        timeout: 3500,
        ...conf
    });
}

const toastStatus = (eOrStatus, messages = {}, opts = {}) => {
    const status = typeof eOrStatus === "object" ? eOrStatus?.response?.status || eOrStatus.code : eOrStatus;
    const conf = opts.conf || {};
    const { head, message, type } = getMessage(status, messages);

    showToast(message, type, conf)
}

const toast = {
    info: (title, conf = {}) => showToast(title, "info", conf),
    error: (title, conf = {}) => showToast(title, "error", conf),
    success: (title, conf = {}) => showToast(title, "success", conf),
    warning: (title, conf = {}) => showToast(title, "warning", conf),
    status: toastStatus
}

export default toast;