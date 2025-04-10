import runtime from "../../../../../dev/runtime/runtime";

const { 
    UI,
    Icons,
    useState
} = runtime;

export default function MailjetForm({ styles, errors, editData, loading }) {
    const [showpwd, setShowPwd] = useState(false);
    const [showSecret, setShowSecret] = useState(false);

    return (
        <>
            <div>
                <div className={styles.label}>API KEY<sup className="d5req">*</sup></div>
                <div className="d5rel">
                    <UI.Input disabled={loading} name="apikey" className={styles.pwinput} defaultValue={editData?.apikey || ""} type={showpwd ? "text" : "password"} autoComplete="off" />
                    <Icons.Fa i={showpwd ? "eye-slash" : "eye"} className={`${styles.pwtoggle} d5clk`} onClick={() => setShowPwd(!showpwd)} />
                </div>
                {!!errors.apikey && <div className="d5ferr">{errors.apikey}</div>}
            </div>

            <div>
                <div className={styles.label}>SECRET KEY<sup className="d5req">*</sup></div>
                <div className="d5rel">
                    <UI.Input disabled={loading} name="secretkey" className={styles.pwinput} defaultValue={editData?.secretkey || ""} type={showSecret ? "text" : "password"} autoComplete="off" />
                    <Icons.Fa i={showSecret ? "eye-slash" : "eye"} className={`${styles.pwtoggle} d5clk`} onClick={() => setShowSecret(!showSecret)} />
                </div>
                {!!errors.secretkey && <div className="d5ferr">{errors.secretkey}</div>}
            </div>
        </>
    )
}