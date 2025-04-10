import runtime from "../../../dev/runtime/runtime";
import env from "../../../dev/src/env";
import ChartReport from "./chart-report";

import dashStyles from "./dash.uniq.css";

const { Icons: { Fa, Lod }, wirec, useState, useEffect, UI, toast } = runtime;

export default function ModEmailDash({ data: dash }) {
    const [refreshing, setRefreshing] = useState(0);

    let head = "Wait..";
    let message = "Checking status";

    const { emails_enabled: enabled } = dash;

    if (enabled === true) {
        head = "Enabled";
        message = "Emails are enabled";
    }

    if (enabled === false) {
        head = "Disabled";
        message = "Emails are disabled";
    }

    const refresh = () => {
        setRefreshing(true);

        wirec.put("load_dashboard", (code) => {
            if (code !== 200) {
                toast.status(code);
            }

            setRefreshing(false);
        });
    }

    wirec.ons("refresh_dash", refresh);

    return (
        <div className="d5pd3">

            <div className="d5flex d5gap20">

                <div className={`d5flex d5pd d5cnom d5gap10 d5ball d5r4`}>
                    {enabled === null && <div className="d5lh1" style={{ fontSize: "14px" }}><Lod /></div>}
                    {enabled !== null && (
                        <div className={`${dashStyles.ind} ${enabled ? "d5bgreen" : "d5bred"} d5blink`}></div>
                    )}
                    <div>
                        <div className="d5f14 d5lh1">{head}</div>
                        <div className="d5f12 d5dim d5mt6">{message}</div>
                    </div>
                </div>

                <div className={`d5flex d5pd d5cnom d5gap10 d5ball d5r4`}>
                    <div className={`d5cgreen`} style={{ marginTop: "-3px" }}><Fa i="check" /></div>
                    <div>
                        <div className="d5f14 d5lh1">Sent</div>
                        <div className="d5f12 d5dim d5mt6">{dash.count_sent} email{dash.count_sent === 1 ? "" : "s"} sent</div>
                    </div>
                </div>

                <div className={`d5flex d5pd d5cnom d5gap10 d5ball d5r4`}>
                    <div className={`d5cred`} style={{ marginTop: "-3px" }}><Fa i="times" /></div>
                    <div>
                        <div className="d5f14 d5lh1">Failed</div>
                        <div className="d5f12 d5dim d5mt6">{dash.count_failed} email{dash.count_failed === 1 ? "" : "s"} failed</div>
                    </div>
                </div>

                <div className="d5cflex d5pd2 d5csec d5gap10 d5ball d5r4">
                    <Fa i="rotate-right" onClick={() => !refreshing && refresh()} className={`${refreshing ? 'd5dim fa-spin' : 'd5clk'}`} />
                </div>

            </div>


            <ChartReport
                chart={dash.chart}
            />

            <div className={`${dashStyles.custom} d5acflex d5pd2 d5cnom`}>
                <div className="d5f18 d5grow">Would you like this plugin to be customized or have more features added to it for you?</div>
                <UI.Button type="s" href={`https://dym5.com/get-in-touch/?ref=${env.pro ? 'pekdash' : 'fekdash'}`} target="_blank">Get In Touch</UI.Button>
            </div>

        </div>
    )
}