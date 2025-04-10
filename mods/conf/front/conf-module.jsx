import runtime from "../../../dev/runtime/runtime"
import env from "../../../dev/src/env";

import styles from "./conf.uniq.css";

const {
    UI,
    Icons,
    useState,
    useRef,
    useEffect,
    toast,
    api,
    utils,
    wirec
} = runtime;

export default function ModEmailConf({ data }) {
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({});
    const [enableDefault, setEnableDefault] = useState(data.senderenable === "1");
    const [enableDomains, setEnableSites] = useState(data.onlysites === "1");
    const [enableFallbacks, setEnableFallbacks] = useState(data.enablefallbacks === "1");
    const [enableNotifications, setEnableNotifications] = useState(data.enablenotifications === "1");

    const disableEmailRef = useRef();
    const nameInputRef = useRef();
    const emailInputRef = useRef();


    const onSave = () => {
        setLoading(true);
        setErrors({});

        const payload = {
            disableemails: disableEmailRef.current.checked ? "1" : "0",
            senderenable: enableDefault ? "1" : "0",
            sendername: nameInputRef.current.value,
            senderemail: emailInputRef.current.value,
        }

        api.post(["default", "smtp_settings"], payload)
            .then(({ data: { status, payload } }) => {
                if (status === 422) {
                    setErrors(payload)
                }

                if (status === 200) {
                    let noLicense = false;

                    if ( ! noLicense ) {
                        wirec.state.set("settings", { ...payload });
                        wirec.put("refresh_dash");
                    }

                    toast.success("Settings saved.");
                }
            })
            .catch((e) => {
                toast.status(e);
            })
            .finally(() => {
                setLoading(false);
            });

    }

    return (
        <>
            <div className="d5pd3">
                <div className={`${styles.confw} d5r4 d5ball`}>
                    <div className={`d5pd2 d5acflex d5gap10 ${styles.bb}`}>
                        <UI.Switch disabled={loading} ref={disableEmailRef} defaultChecked={data.disableemails === "1"} className="d5ib" _style={{ marginBottom: "-4px" }} />
                        <div className="d5lh1 d5cnom d5dim">Disable emails</div>
                    </div>

                    <div className={`d5pd2 d5flex d5gap10`}>
                        <UI.Switch disabled={loading} checked={enableDefault} onChange={(e) => setEnableDefault(e.target.checked)} className="d5ib" />

                        <div className="d5lh1 d5cnom d5grow" style={{ paddingTop: "7px" }}>
                            <div className="d5dim">Default sender</div>
                            <div className="d5f12 d5dim d5mt6">Set up default sender name and email.</div>

                            <div className="d5mt20" style={{ display: enableDefault ? "block" : "none" }}>
                                <div className="d5mt14">
                                    <div className="d5smlb d5acflex d5csec">NAME<sup className="d5req d5ml6">*</sup></div>
                                    <UI.Input disabled={loading} defaultValue={data.sendername || ""} spellCheck={false} autoComplete="off" ref={nameInputRef} />
                                    {!!errors.sendername && <div className="d5ferr">{errors.sendername}</div>}
                                </div>

                                <div className="d5mt14">
                                    <div className="d5smlb d5acflex d5csec">EMAIL<sup className="d5req d5ml6">*</sup></div>
                                    <UI.Input disabled={loading} defaultValue={data.senderemail || ""} spellCheck={false} autoComplete="off" ref={emailInputRef} />
                                    {!!errors.senderemail && <div className="d5ferr">{errors.senderemail}</div>}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className={`${styles.confw} d5cnom d5mt20 d5pd2 d5r4 d5ball`}>
                    <div className="d5flex d5gap10">
                        <UI.Switch
                            disabled={loading || !env.pro}
                            checked={env.pro ? enableFallbacks : false}
                            onChange={(e) => setEnableFallbacks(e.target.checked)}
                        />

                        <div>
                            <div className="d5csec d5fwb d5pt4">Fallback profiles</div>
                            <div className="d5f12 d5dim d5mt6">Set up the profiles to use to send email again if sending email fails with the default configuration.</div>
                        </div>
                    </div>

                    <div className="d5mt10">
                        {!env.pro && (
                            <div className="d5tc d5cbox d5pd1h">
                                This feature is only available in pro plan.
                                <div className="d5mt6"><a href={`${env.buylink}?ref=fallbackprofile`} target="_blank" className="d5link2">Buy Now ↗</a></div>
                            </div>
                        )}
                    </div>
                </div>

                <div className={`${styles.confw} d5pd2 d5cnom d5mt20 d5r4 d5ball`}>
                    <div className="d5flex d5gap10">
                        <UI.Switch
                            disabled={loading || !env.pro}
                            checked={env.pro ? enableDomains : false}
                            onChange={(e) => setEnableSites(e.target.checked)}
                        />

                        <div>
                            <div className="d5csec d5fwb d5pt4">Send only sites</div>
                            <div className="d5f12 d5dim d5mt6">This setting prevents emails from being sent accidentally from unwanted environments.</div>
                        </div>
                    </div>

                    <div className="d5mt10">
                        {!env.pro && (
                            <div className="d5tc d5cbox d5pd1h">
                                This feature is only available in pro plan.
                                <div className="d5mt6"><a href={`${env.buylink}?ref=sosites`} target="_blank" className="d5link2">Buy Now ↗</a></div>
                            </div>
                        )}

                    </div>
                </div>

                <div className={`${styles.confw} d5pd2 d5cnom d5mt20 d5r4 d5ball`}>
                    <div className="d5flex d5gap10">
                        <UI.Switch
                            disabled={loading || !env.pro}
                            checked={env.pro ? enableNotifications : false}
                            onChange={(e) => setEnableNotifications(e.target.checked)}
                        />

                        <div>
                            <div className="d5csec d5fwb d5pt4">Notifications</div>
                            <div className="d5f12 d5dim d5mt6">Receive notifications about email sending failures.</div>
                        </div>
                    </div>

                    {!env.pro && (
                        <div className="d5tc d5cbox d5mt10 d5pd1h">
                            This feature is only available in pro plan.
                            <div className="d5mt6"><a href={`${env.buylink}?ref=notif`} target="_blank" className="d5link2">Buy Now ↗</a></div>
                        </div>
                    )}
                </div>


                <div className={`${styles.confw} d5mt20`}>
                    <UI.Button type="s" onClick={onSave} disabled={loading} buttonType="submit" className="d5fw">
                        {loading ? <Icons.Lod /> : <Icons.Fa i="check" />}
                        &nbsp;&nbsp;Save settings
                    </UI.Button>
                </div>
            </div>
        </>
    )
}