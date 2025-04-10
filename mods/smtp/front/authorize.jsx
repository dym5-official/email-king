import runtime from "../../../dev/runtime/runtime";

const {
    UI,
    Icons,
    useState,
    toast,
    api
} = runtime;

export default function Authorize({ item, setAuthorize, onUpdate }) {
    const [loading, setLoading] = useState(false);

    const handleConfirm = () => {
        setLoading(true);

        api.post(['default', 'manage_smtp_profiles'], { id: item._id, action: 'get_profile' })
            .then(({ data: { status, payload } }) => {
                if (status === 200) {
                    if (!payload?.auth_url) {
                        onUpdate(payload._id, payload);
                        toast.success("Authorized successfully.");
                        setAuthorize(false);
                    } else {
                        toast.error("Not yet authorized");
                    }

                    return;
                }

                toast.status(status);
            })
            .catch((e) => toast.status(e))
            .finally(() => setLoading(false))
    }

    const props = {};

    if ( !loading ) {
        props.href = item.auth_url;
    }

    return (
        <UI.Modal style={{ height: "auto" }} onClose={() => setAuthorize(false)} loading={loading}>
            <div className="d5pd2 d5cnom">
                <div className="d5f20 d5fwb">{item.name}</div>
                <div className="d5mt6 d5dim d5f12">Authorization is required for this profile to send emails.</div>
                <div className="d5mt6 d5dim7 d5f12 d5cpri">Please click the authorize button, which will open a link in a new tab. After authorization click the confirm button.</div>
                <div className="d5acflex d5gap10 d5mt10">
                    <div className="d5grow"><UI.Button disabled={loading} className="d5fw" target="_blank" {...props} type="s"><Icons.Fa i="lock-open" /> Authorize</UI.Button></div>
                    <UI.Button disabled={loading} onClick={handleConfirm}>{loading ? <Icons.Lod /> : <Icons.Fa i="check" />} Confirm</UI.Button>
                </div>
            </div>
        </UI.Modal>
    )
}