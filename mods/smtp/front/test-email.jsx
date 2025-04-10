import runtime from "../../../dev/runtime/runtime";

const {
    UI,
    Icons,
    useState,
    useRef,
    toast,
    api,
    utils
} = runtime;

export default function TestEmail({ profiles, admin_email, onClose }) {
    const [sending, setSending] = useState(false);
    const [errors, setErrors] = useState({});
    const [result, setResult] = useState();

    const form = useRef();

    const handleSend = (e) => {
        e.preventDefault();

        setSending(true);
        setErrors({});
        setResult({});

        api.post(['default', 'smtp_send_test_email'], utils.formdata(form.current, { html: "0" }))
            .then(({ data: { payload, status } }) => {
                if (status === 422) {
                    return setErrors(payload);
                }

                if (status === 200 || status === 500) {
                    return setResult({ status, payload });
                }

                toast.status(status);
            })
            .finally(() => setSending(false));

        return false;
    }

    return (
        <UI.Modal loading={sending} onClose={onClose} style={{ height: "auto" }}>
            <div className="d5cnom d5hbg d5pd2">
                <div className="d5f20 d5cpri"><strong>Test Email</strong></div>
                <div className="d5f14 d5mt6 d5dim">Send a test email to check if an email provider is working.</div>

                <form onSubmit={handleSend} ref={form}>
                    <div className="d5mt20">
                        <div className="d5smlb d5csec d5acflex d5gap6">Recipient<sup className="d5req">*</sup></div>
                        <UI.Input disabled={sending} name="email" autoComplete="off" spellCheck={false} defaultValue={admin_email} placeholder="someone@example.com" />
                        {!!errors.email && <div className="d5ferr">{errors.email}</div>}
                    </div>

                    <div className="d5mt14">
                        <div className="d5smlb d5csec d5acflex d5gap6">Select profile<sup className="d5req">*</sup></div>
                        <UI.Select name="profile" disabled={sending}>
                            <option value=""></option>
                            <option value="default">* Current configuration</option>
                            <option value="php">* Default PHP</option>
                            {profiles.length > 0 && (<option value="" disabled>-----------------------------</option>)}
                            {profiles.map((profile) => {
                                return (
                                    <option value={profile._id} key={profile._id}>{profile.name}</option>
                                )
                            })}
                        </UI.Select>
                        {!!errors.profile && <div className="d5ferr">{errors.profile}</div>}
                    </div>

                    <div className="d5mt20 d5acflex d5lh1 d5gap10">
                        <UI.Switch value="1" name="html" disabled={sending} defaultChecked={true} /> HTML&nbsp;&nbsp;
                        <div className="d5grow">
                            <UI.Button disabled={sending} buttonType="submit" onClick={handleSend} className="d5fw" type="s">{sending ? <Icons.Lod /> : <Icons.Fa i="arrow-right" />}&nbsp;&nbsp;Send</UI.Button>
                        </div>
                    </div>
                </form>

                {result?.status === 500 && (
                    <div className="d5ball d5cred d5r4 d5pd d5mt20">
                        <strong>FAILED</strong>
                        <div className="d5mt6 d5dim" style={{wordBreak:"break-word"}}>{result.payload}</div>
                    </div>
                )}

                {result?.status === 200 && (
                    <div className="d5ball d5csec d5r4 d5pd d5mt20">
                        <strong>SUCCEEDED</strong>
                        <div className="d5mt6 d5dim">Test email seems to be sent successfully, please check your inbox to make sure.</div>
                    </div>
                )}
            </div>
        </UI.Modal>
    )
}