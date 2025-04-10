import runtime from "../../../dev/runtime/runtime";

const {
    UI,
    Icons,
    useState,
    toast,
    api
} = runtime;

export default function VerifyCreds({ item, setCredsVerify, onUpdate, callback = null }) {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");

    const handleConfirm = () => {
        setLoading(true);

        api.post(['default', 'manage_smtp_profiles'], { id: item._id, action: "verify" })
            .then(({ data: { status, payload } }) => {
                if (status === 400) {
                    setError(payload);
                } else if (status === 200) {
                    onUpdate(item._id, payload);
                    toast.success("Verified successfully");
                    setCredsVerify(false);

                    if (typeof callback === "function") {
                        callback();
                    }
                } else {
                    toast.status(status);
                }
            })
            .catch((e) => toast.status(e))
            .finally(() => setLoading(false))
    }

    const props = {};

    if (!loading) {
        props.href = item.auth_url;
    }

    return (
        <UI.Modal style={{ height: "auto" }} onClose={() => setCredsVerify(false)} loading={loading}>
            <div className="d5pd2 d5cnom">
                <div className="d5f20 d5fwb">{item.name}</div>
                <div className="d5mt6 d5dim d5f12">{item.verification.desc}</div>
                <div className="d5acflex d5gap10 d5mt10">
                    <UI.Button disabled={loading} onClick={handleConfirm} className="d5grow" type="s">{loading ? <Icons.Lod /> : <Icons.Fa i="check" />} Verify</UI.Button>
                </div>
                {!!error && (<div className="d5ferr d5mt6">{error}</div>)}
            </div>
        </UI.Modal>
    )
}