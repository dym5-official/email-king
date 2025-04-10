import runtime from "../runtime/runtime";
import styles from "./style/app.uniq.css";

import ModEmailSMTP from "../../mods/smtp/front/smtp-module";
import ModEmailLogs from "../../mods/logs/front/log-module";
import ModEmailSend from "../../mods/send/front/send-module";
import ModEmailDash from "../../mods/dash/front/dash-module";
import ModEmailConf from "../../mods/conf/front/conf-module";

const { useState, useEffect, api, Icons, wirec } = runtime;

export default function App() {
    const [tab, setTab] = useState("dash");

    // Data for each module
    const [dashData, setDashData] = useState(null);
    const [smtpData, setSmtpData] = useState(null);
    const [logsData, setLogsData] = useState(null);

    useEffect(() => {
        const callback = (cb, code) => {
            if ( typeof cb === "function" ) {
                cb(code);
            }
        }

        wirec.ons("load_dashboard", (cb = null) => {
            api.get(["default", "get_dashboard"])
                .then(({ data: { status, payload } }) => {
                    if (status === 200) {
                        setDashData({ ...payload });
                    }

                    callback(cb, status);
                })
                .catch((e) => callback(cb, e))
        });

        wirec.put("load_dashboard");

        wirec.ons("load_smtp_profiles", (cb = null) => {
            api.get(["default", "get_smtp_profiles"])
                .then(({ data: { status, payload } }) => {
                    if (status === 200) {
                        setSmtpData({ ...payload });
                    }

                    callback(cb, status);
                })
                .catch((e) => callback(cb, e))
        });

        wirec.put("load_smtp_profiles");

        api.get(["default", "get_logs"])
            .then(({ data: { status, payload } }) => {
                if (status === 200) {
                    setLogsData(payload);
                }
            })
            .catch((e) => {
                // ..
            })
    }, []);

    return (
        <div className={`${styles.appw}`}>
            <div className={`${styles.tabs} d5cnom d5nosel`}>
                <a className={tab === "dash" ? styles.active : ""} onClick={() => setTab("dash")}>
                    <TabIcon path={"mods/dash/icon.svg"} />
                </a>

                <a className={tab === "prov" ? styles.active : ""} onClick={() => setTab("prov")}>
                    <TabIcon path={"mods/smtp/icon.svg"} />
                </a>

                <a className={tab === "logs" ? styles.active : ""} onClick={() => setTab("logs")}>
                    <TabIcon path={"mods/logs/icon.svg"} />
                </a>

                <a className={tab === "send" ? styles.active : ""} onClick={() => setTab("send")}>
                    <TabIcon path={"mods/send/icon.svg"} />
                </a>

                <a className={tab === "conf" ? styles.active : ""} onClick={() => setTab("conf")}>
                    <TabIcon path={"mods/conf/icon.svg"} />
                </a>
            </div>

            <div className={`${styles.body} d5ascroll`} style={{ display: tab === "dash" ? "block" : "none" }}>
                {dashData === null
                    ? <Loading />
                    : <ModEmailDash data={dashData} />
                }
            </div>

            <div className={`${styles.body} d5ascroll`} style={{ display: tab === "prov" ? "block" : "none" }}>
                {smtpData === null
                    ? <Loading />
                    : <ModEmailSMTP data={smtpData} />
                }
            </div>

            <div className={`${styles.body} d5ascroll`} style={{ display: tab === "logs" ? "block" : "none" }}>
                {logsData === null
                    ? <Loading />
                    : <ModEmailLogs data={logsData} />
                }
            </div>

            <div className={`${styles.body} d5ascroll`} style={{ display: tab === "send" ? "block" : "none" }}>
                <ModEmailSend />
            </div>

            <div className={`${styles.body} d5ascroll`} style={{ display: tab === "conf" ? "block" : "none" }}>
                {smtpData === null
                    ? <Loading />
                    : <ModEmailConf data={smtpData.settings} />
                }
            </div>
        </div>
    )
}

function TabIcon({ path }) {
    return (
        <img src={`${window.VARS.url}/${path}`} alt="" className={styles.tabicon} />
    )
}

function Loading() {
    return (
        <div className="d5fc d5cnom d5dim">
            <Icons.Lod style={{ fontSize: "28px" }} />
        </div>
    )
}