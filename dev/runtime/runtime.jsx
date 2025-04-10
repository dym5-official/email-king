import {
    useState,
    useEffect,
    createElement,
    useRef,
    Fragment
} from "react";

import ReactDOM from "react-dom/client";

import Icons from "./icons";
import UI from "./ui";
import toast from "./toast/toast";
import api from "./api";
import utils from "./utils";
import wirec from "wirec";
import axios from "axios";

export const render = (selector, component) => {
    const root = document.querySelector(selector);

    if ( root ) {
        ReactDOM.createRoot(root).render(component)
    }
}

const runtime = {
    useState,
    useEffect,
    useRef,
    Fragment,
    h: createElement,
    UI,
    toast,
    Icons,
    api,
    utils,
    wirec,
    axios
};

export default runtime;