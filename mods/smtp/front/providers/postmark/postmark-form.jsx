import runtime from "../../../../../dev/runtime/runtime";

const { 
    UI,
    Icons,
    useState
} = runtime;

export default function PostmarkForm({ styles, errors, editData, loading }) {
    const [showpwd, setShowPwd] = useState(false);

    return (
        <>
            <div>
                <div className={styles.label}>Server API Token<sup className="d5req">*</sup></div>
                <div className="d5rel">
                    <UI.Input disabled={loading} name="apikey" className={styles.pwinput} defaultValue={editData?.apikey || ""} type={showpwd ? "text" : "password"} autoComplete="off" />
                    <Icons.Fa i={showpwd ? "eye-slash" : "eye"} className={`${styles.pwtoggle} d5clk`} onClick={() => setShowPwd(!showpwd)} />
                </div>
                {!!errors.apikey && <div className="d5ferr">{errors.apikey}</div>}
            </div>
        </>
    )
}