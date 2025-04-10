import App from "./app";
import env from "./env";
import runtime from "../runtime/runtime";


const { wirec, useState } = runtime;

const components = {
    a: App,
};

export default function Entry() {
    const code = env.pro
        ? (window.VARS.acpin && Number(window.VARS.acpin) > 300)
            ? "a"
            : "i"
        : "a";

    const [status] = wirec.state.init("apstau", useState, code);
    const Render = components[status];

    return <Render />
}