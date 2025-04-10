import runtime from "../../../../../dev/runtime/runtime";

const { 
    UI,
    Icons,
    useState
} = runtime;

export default function OutlookForm({ styles, errors, editData, loading }) {
    const [showpwd, setShowPwd] = useState(false);

    return (
        <>
            <div>
                <div className={styles.label}>APPLICATION ID<sup className="d5req">*</sup></div>
                <UI.Input disabled={loading} name="appid" defaultValue={editData?.appid || ""} autoComplete="off" />
                {!!errors.appid && <div className="d5ferr">{errors.appid}</div>}
            </div>

            <div>
                <div className={styles.label}>APPLICATION PASSWORD<sup className="d5req">*</sup></div>
                <div className="d5rel">
                    <UI.Input disabled={loading} className={styles.pwinput} name="apppass" defaultValue={editData?.apppass || ""} type={showpwd ? "text" : "password"} autoComplete="off" />
                    <Icons.Fa i={showpwd ? "eye-slash" : "eye"} className={`${styles.pwtoggle} d5clk`} onClick={() => setShowPwd(!showpwd)} />
                </div>
                {!!errors.apppass && <div className="d5ferr">{errors.apppass}</div>}
            </div>

            <div>
                <div className={styles.label}>REDIRECT URI</div>
                <UI.Input defaultValue={window.VARS.cburl} readOnly />
            </div>
        </>
    )
}