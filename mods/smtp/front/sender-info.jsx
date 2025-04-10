import { styles } from "./profile-form";
import { providers } from "./profile-form";
import runtime from "../../../dev/runtime/runtime";

const {
    UI,
    useState,
    useRef,
    useEffect,
    Icons,
    toast,
    api,
    utils
} = runtime;

export default function SenderInfo({ item, setSetupSender, onUpdate, settings }) {
    const { senderopts, customsender } = item;

    const [csender, setCSender] = useState(senderopts.must ? "1" : customsender);
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({});
    const [emailNameWidth, setEmailNameWidth] = useState("52px");
    /**
     * fixed   = fixed emails
     * fdomain = fixed domains
     * any     = any email, a text input field
     */
    const [emailType, setEmailType] = useState("");
    const [theDomains, setTheDomains] = useState(senderopts?.email?.domain?.opts || []);

    const emailNameShadow = useRef();
    const emailNameRef = useRef();

    useEffect(() => {
        const type = senderopts.email.fixed
            ? "fixed"
            : senderopts.email.domain.any
                ? "any"
                : "fdomain"

        const updateSize = () => {
            setTimeout(handleEmailNameInput, 200, {
                target: {
                    value: item.emailname
                }
            })
        }

        if (!senderopts.both) {
            setEmailType(type);

            if (type === "fdomain") {
                updateSize();
            }
        }

        if ( item.provider === "awsses" && item.idtype === "domain" && item.identity ) {
            setEmailType("fdomain");
            setTheDomains([item.identity]);
            
            if ( item.emailname ) {
                updateSize();
            }
        }
    }, []);

    const handleIdentityChange = (e) => {
        let value = e.target.value.trim();

        if (value && value.indexOf("@") === -1) {
            setEmailType("fdomain");
            setTheDomains([value]);
            
            setTimeout(() => {
                handleEmailNameInput({
                    target: {
                        value: emailNameRef.current.value
                    }
                })
            }, 10);

            return;
        }

        setEmailType("");
    }

    const handleCheckbox = (e) => {
        if (!senderopts.must) {
            setCSender(String(e.target.value));
        }
    }

    const provider = providers[item.provider];

    const handleSubmit = (e) => {
        e.preventDefault();

        if (loading) {
            return false;
        }

        setErrors({});
        setLoading(true);

        const data = utils.formdata(e.target, {
            id: item._id,
            customsender: csender,
            action: "configure_sender",
        });

        api.post(["default", "manage_smtp_profiles"], data)
            .then(({ data: { status, payload } }) => {
                if (status === 422) {
                    return setErrors(payload);
                }

                if (status === 200) {
                    toast.success("Updated successfully.");
                    onUpdate(payload._id, payload);
                    return;
                }

                toast.status(status);
            })
            .catch((e) => toast.status(e))
            .finally(() => setLoading(false))

        return false;
    }

    const handleEmailNameInput = (e) => {
        const { value } = e.target;
        emailNameShadow.current.textContent = value;

        let width = emailNameShadow.current.getBoundingClientRect().width + 13;

        if (value.length === 0) {
            width = 52;
        }

        if (width > 140) {
            width = 140;
        }

        setEmailNameWidth(`${width}px`);
    }

    return (
        <UI.Modal style={{ height: "auto", width: "360px" }}>
            <form onSubmit={handleSubmit}>
                <div className={`d5cnom ${styles.formw} d5hbg`}>
                    <div className="d5pd2tbh">
                        <div className="d5acflex d5gap6">
                            <div className="d5grow d5cpri"><strong>{item.name}</strong></div>
                            <img className="d5block d5dim" title={provider.label} src={`${window.VARS.url}/assets/${provider.icon}`.replace("./", "")} style={{ height: "12px" }} />
                            <div className="d5lh1 d5f12 d5dim">{provider.label}</div>
                        </div>

                        <div className="d5f12 d5dim d5mt6">Set up sender name and email for this profile.</div>
                    </div>

                    <div className="d5bgb d5pd2tbh d5pb20 d5r8b">
                        <div className="d5pdt6">
                            <div className={styles.label}>SENDER INFO<sup className="d5req">*</sup></div>
                            {/* <div className="d5f12 d5dim d5mb6" style={{marginTop:"-4px"}}>Set up sender name and email for this profile.</div> */}
                            <div className="d5acflex d5f14 d5lh1">
                                <div className="d5w50"><UI.Checkbox type="radio" mb="-2px" onChange={handleCheckbox} value="0" round size="16px" checked={csender === "0"}>&nbsp;&nbsp;Default</UI.Checkbox></div>
                                <div><UI.Checkbox type="radio" mb="-2px" onChange={handleCheckbox} value="1" round size="16px" checked={csender === "1"}>&nbsp;&nbsp;Custom</UI.Checkbox></div>
                            </div>
                            {!!errors.customsender && <div className="d5ferr">{errors.customsender}</div>}
                        </div>

                        {csender === "0" && (
                            <div className="d5f12 d5dim d5mt14">By default, <strong>{settings.sendername}</strong> from <strong>{settings.senderemail}</strong> will be used. You have the option to modify the default sender information in the settings.</div>
                        )}

                        {csender === "1" && (
                            <>
                                <div className="d5mt18">
                                    <div className={styles.label}>NAME{csender && <sup className="d5req">*</sup>}</div>

                                    {senderopts.name.any ? (
                                        <UI.Input disabled={loading} name="fromname" defaultValue={item?.fromname || ""} autoComplete="off" />
                                    ) : (
                                        <UI.Select disabled={loading} name="fromname" defaultValue={item?.fromname || ""}>
                                            {senderopts.name.opts.map((name) => {
                                                return (
                                                    <option value={name} key={name}>{name}</option>
                                                )
                                            })}
                                        </UI.Select>
                                    )}

                                    {!!errors.fromname && csender && <div className="d5ferr">{errors.fromname}</div>}
                                </div>

                                {item.provider === "awsses" && senderopts.both && (
                                    <div className="d5mt14">
                                        <div className={styles.label}>SELECT IDENTITY{csender && <sup className="d5req">*</sup>}</div>
                                        <UI.Select disabled={loading} name="identity" onChange={handleIdentityChange} defaultValue={item?.identity || ""}>
                                            <option value=""></option>
                                            {senderopts.email.fixed.map((em) => {
                                                return (
                                                    <option value={em} key={em}>{em}</option>
                                                )
                                            })}
                                            {senderopts.email.domain.opts.map((em) => {
                                                return (
                                                    <option value={em} key={em}>{em}</option>
                                                )
                                            })}
                                        </UI.Select>

                                        {!!errors.identity && csender && <div className="d5ferr">{errors.identity}</div>}
                                    </div>
                                )}

                                {!!emailType && (
                                    <div className="d5mt14">
                                        <div className={styles.label}>EMAIL{csender && <sup className="d5req">*</sup>}</div>
                                        {senderopts.email?.any && (<UI.Input disabled={loading} name="fromemail" defaultValue={item?.fromemail || ""} autoComplete="off" />)}
                                        {emailType === "any" && (<UI.Input disabled={loading} name="fromemail" defaultValue={item?.fromemail || ""} autoComplete="off" />)}
                                        {emailType === "fixed" && (
                                            <UI.Select disabled={loading} name="fromemail" defaultValue={item?.fromemail || ""}>
                                                {senderopts.email.fixed.map((email) => {
                                                    return (
                                                        <option value={email} key={email}>{email}</option>
                                                    )
                                                })}
                                            </UI.Select>
                                        )}
                                        <pre className="d5lh1 d5f14 d5ib d5abs" style={{ fontFamily: "inherit", visibility: "hidden", bottom: "-700px" }} ref={emailNameShadow}></pre>
                                        {emailType === "fdomain" && (
                                            <div className="d5cbox d5acflex">
                                                <UI.Input
                                                    borderless={true}
                                                    autoComplete="off"
                                                    name="emailname"
                                                    className="d5tc"
                                                    onChange={handleEmailNameInput}
                                                    ref={emailNameRef}
                                                    placeholder="_____"
                                                    style={{
                                                        paddingRight: "2px",
                                                        width: emailNameWidth,
                                                    }}
                                                    defaultValue={item.emailname}
                                                />
                                                <span className="d5ib" style={{ marginRight: "-2px" }}>@</span>

                                                {theDomains.length === 1 && (
                                                    <UI.Input defaultValue={theDomains[0]} name="emaildomain" style={{ paddingLeft: "4px" }} readOnly borderless key={theDomains[0]} />
                                                )}

                                                {theDomains.length > 1 && (
                                                    <UI.Select name="emaildomain" borderless={true} className="d5grow" defaultValue={item.emaildomain} style={{ paddingLeft: 0 }}>
                                                        {theDomains.map((domain) => {
                                                            return (
                                                                <option key={domain} value={domain}>{domain}</option>
                                                            )
                                                        })}
                                                    </UI.Select>
                                                )}
                                            </div>
                                        )}

                                        {!!errors.emailname && csender && <div className="d5ferr">{errors.emailname}</div>}
                                        {!!errors.fromemail && csender && <div className="d5ferr">{errors.fromemail}</div>}
                                    </div>
                                )}
                            </>
                        )}

                        <div className="d5mt18 d5gap10 d5flex">
                            <UI.Button disabled={loading} className="d5grow" buttonType="submit" type="s">{loading ? <Icons.Lod /> : <Icons.Fa i="check" />} Update</UI.Button>
                            <UI.Button disabled={loading} type="d" buttonType="button" onClick={() => !loading && setSetupSender(false)}><Icons.Fa i="times" /> Close</UI.Button>
                        </div>
                    </div>
                </div>
            </form>
        </UI.Modal>
    )
}