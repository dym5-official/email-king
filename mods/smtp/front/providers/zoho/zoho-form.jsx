import runtime from "../../../../../dev/runtime/runtime";

const { 
    UI,
    Icons,
    useState
} = runtime;

export default function ZohoForm({ styles, errors, editData, loading }) {
    const [showpwd, setShowPwd] = useState(false);

    return (
        <>
            <div>
                <div className={styles.label}>CLIENT ID<sup className="d5req">*</sup></div>
                <UI.Input disabled={loading} name="clientid" defaultValue={editData?.clientid || ""} autoComplete="off" />
                {!!errors.clientid && <div className="d5ferr">{errors.clientid}</div>}
            </div>

            <div>
                <div className={styles.label}>CLIENT SECRET<sup className="d5req">*</sup></div>
                <div className="d5rel">
                    <UI.Input disabled={loading} name="clientsecret" defaultValue={editData?.clientsecret || ""} type={showpwd ? "text" : "password"} autoComplete="off" className={styles.pwinput} />
                    <Icons.Fa i={showpwd ? "eye-slash" : "eye"} className={`${styles.pwtoggle} d5clk`} onClick={() => setShowPwd(!showpwd)} />
                </div>
                {!!errors.clientsecret && <div className="d5ferr">{errors.clientsecret}</div>}
            </div>

            <div>
                <div className={styles.label}>REDIRECT URI</div>
                <UI.Input defaultValue={window.VARS.cburl} readOnly />
            </div>
        </>
    )
}