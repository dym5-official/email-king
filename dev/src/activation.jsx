import runtime from "../runtime/runtime"

import styles from "./style/activation.uniq.css"

const { UI, Icons: { Fa, Lod }, useState, useRef, api, toast, wirec } = runtime;

export default function Activation() {
    const licenseInputRef = useRef();

    const [loading, setLoading] = useState(false);
    const [err, setErr] = useState("");

    const onActivate = () => {
        if (loading) {
            return;
        }

        setLoading(true);
        setErr("");

        api.post(['default', 'activation'], { license: licenseInputRef.current.value })
            .then(({ data: { payload: { success, message } }}) => {
                if (!success) {
                    setErr(message);
                    return;
                }

                wirec.state.set("apstau", "a");

                toast.success("Activated successfully.");
            })
            .catch((e) => {
                toast.status(e);
            })
            .finally(() => {
                setLoading(false);
            });
    }

    return (
        <div className="d5fc">
            <div className={`d5cbox d5pd3 d5cnom ${styles.box}`}>
                <div className="d5flex d5gap10">
                    <div className="d5f22 d5csec"><Fa i="key" /></div>
                    <div>
                        <div className="d5f22 d5csec">Activation</div>
                        <div className="d5f16 d5dim d5mt6">The plugin is not activated. Please enter your license key to activate it.</div>
                    </div>
                </div>

                <div className="d5mt20">
                    <UI.Input
                        disabled={loading}
                        autoComplete="off"
                        placeholder="00000000000000000000-XXXXXXXXXXXXXXXXXXXX-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"
                        ref={licenseInputRef}
                    />
                    {!!err && (<div className="d5ferr">{err}</div>)}
                </div>

                <div className="d5acflex d5mt14">
                    <div className="d5grow d5f20 d5dim4">⦾</div>
                    <div>
                        <UI.Button onClick={onActivate} disabled={loading}>{loading ? <Lod /> : <Fa i="check" />} Activate</UI.Button>
                    </div>
                </div>
            </div>
        </div>
    )
}