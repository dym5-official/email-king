import runtime from "../../../../../dev/runtime/runtime";

const { 
    UI,
    Icons,
    useState
} = runtime;

export default function SparkPostForm({ styles, errors, editData, loading }) {
    const [showpwd, setShowPwd] = useState(false);

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
                <div className={styles.label}>REGION<sup className="d5req">*</sup></div>

                <div className="d5clear">
                    <div className="d5fl d5w50">
                        <UI.Checkbox type="radio" name="sparkpostregion" defaultChecked={editData.sparkpostregion === "us"} value="us" acflex size="18px">&nbsp;&nbsp;US</UI.Checkbox>
                    </div>
                    <div className="d5fl d5w50">
                        <UI.Checkbox type="radio" name="sparkpostregion" defaultChecked={editData.sparkpostregion === "eu"} value="eu" acflex size="18px">&nbsp;&nbsp;EU</UI.Checkbox>
                    </div>
                </div>
                {!!errors.sparkpostregion && <div className="d5ferr">{errors.sparkpostregion}</div>}
            </div>
        </>
    )
}