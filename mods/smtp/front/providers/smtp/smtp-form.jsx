import runtime from "../../../../../dev/runtime/runtime";

const { 
    UI,
    Icons,
    useState,
    useRef,
    useEffect
} = runtime;

export default function SMTPForm({ styles, editData, errors, loading }) {
    const [showpwd, setShowPwd] = useState(false);
    const [auth, setAuth] = useState(false);

    const portInput = useRef();

    useEffect(() => {
        setAuth(editData.auth === "1");
    }, [editData.auth]);

    const handleAuthChange = (e) => setAuth(e.target.checked);

    const handleEncChange = (e) => {
        const { value } = e.target;

        const portMap = {
            "none": "25",
            "ssl": "465",
            "tls": "587",
        };

        portInput.current.value = value === "" ? "" : (portMap[value] || "");
    }

    return (
        <>
            <div className={styles.flex}>
                <div className={styles.grow}>
                    <div className={styles.label}>HOST<sup className="d5req">*</sup></div>
                    <UI.Input disabled={loading} name="host" defaultValue={editData?.host || ""} autoComplete="off" />
                    {!!errors.host && <div className="d5ferr">{errors.host}</div>}
                </div>

                <div>
                    <div className={styles.label} style={{ paddingRight: "8px" }}>ENCRYPTION<sup className="d5req">*</sup></div>
                    <UI.Select name="enc" key={editData.enc} defaultValue={editData.enc || ""} onChange={handleEncChange}>
                        <option value=""></option>
                        <option value="none">NONE</option>
                        <option value="ssl">SSL</option>
                        <option value="tls">TLS</option>
                    </UI.Select>
                    {!!errors.enc && <div className="d5ferr">{errors.enc}</div>}
                </div>

                <div className={styles.port}>
                    <div className={styles.label}>PORT<sup className="d5req">*</sup></div>
                    <UI.Input disabled={loading} name="port" ref={portInput} defaultValue={editData?.port || ""} maxLength={5} autoComplete="off" />
                    {!!errors.port && <div className="d5ferr">{errors.port}</div>}
                </div>
            </div>

            <div className={`d5bb d d5pb10`}>
                <div className={`${styles.label} ${styles.labelw}`}><UI.Checkbox key={editData.autotls} defaultChecked={editData.autotls === "1"} disabled={loading} name="autotls" value="1" size="18px" /> AUTOMATIC TLS</div>
                <div className="d5f14 d5dim">This is recommended, but if this is causing issues, keep disabled.</div>
            </div>

            <div>
                <div className={`${styles.label} ${styles.labelw}`}><UI.Checkbox disabled={loading} onChange={handleAuthChange} checked={auth} name="auth" value="1" size="18px" /> AUTHENTICATION</div>
                <div className="d5f14 d5dim">If authentication is required to send email.</div>
            </div>

            <div>
                <div className={styles.label}>USERNAME{auth && <sup className="d5req">*</sup>}</div>
                <UI.Input disabled={loading} name="username" defaultValue={editData?.username || ""} autoComplete="off" />
                {!!errors.username && auth && <div className="d5ferr">{errors.username}</div>}
            </div>

            <div className="d5pb10">
                <div className={styles.label}>PASSWORD{auth && <sup className="d5req">*</sup>}</div>
                <div className="d5rel">
                    <UI.Input disabled={loading} className={styles.pwinput} name="password" defaultValue={editData?.password || ""} type={showpwd ? "text" : "password"} autoComplete="off" />
                    <Icons.Fa i={showpwd ? "eye-slash" : "eye"} className={`${styles.pwtoggle} d5clk`} onClick={() => setShowPwd(!showpwd)} />
                </div>
                {!!errors.password && auth && <div className="d5ferr">{errors.password}</div>}
            </div>
        </>
    )
}