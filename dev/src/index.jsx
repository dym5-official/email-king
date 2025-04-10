import { render } from "../runtime/runtime";

import Entry from "./entry"

import "./fonts/comme/comme.css";
import "./style/general.css";
import "./style/app.css";
import "./style/animation/animate.css";

window.addEventListener("DOMContentLoaded", () => {
    render("#email-king-app-root", <Entry />);
});