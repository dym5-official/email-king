import env from "../../../dev/src/env"
import runtime from "../../../dev/runtime/runtime";

const { UI, useState, useRef, Icons, toast, api } = runtime;
const { Modal, Input, Textarea, Tabs, Button } = UI

export default function ReSendPopup({ info, onClose }) {
    const [activeTab, setActiveTab] = useState("to");
    const [sending, setSending] = useState(false);
    const [errors, setErrors] = useState({});
    const [err, setErr] = useState("");

    const toRef = useRef();
    const ccRef = useRef();
    const bccRef = useRef();
    const subjectRef = useRef();

    const refs = [toRef, ccRef, bccRef, subjectRef];

    const onClearAll = () => {
        refs.forEach((ref) => {
            ref.current.value = "";
        });
    }

    const onSend = () => {
        if (sending) {
            return;
        }

        setSending(true);
        setErrors({});
        setErr("");

        const payload = refs.reduce((acc, ref) => {
            acc[ref.current.name] = ref.current.value;
            return acc;
        }, { _id: info._id });

        api.post(["default", "resend_email"], payload)
            .then(({ data: { status, payload } }) => {
                if (status === 422) {
                    const keys = Object.keys(payload);

                    for (let key of ['to', 'cc', 'bcc']) {
                        if (keys.indexOf(key) !== -1) {
                            setActiveTab(key);
                            break;
                        }
                    }

                    return setErrors(payload);
                }

                if (status === 200) {
                    if (payload === true) {
                        toast.success("Email sent successfully.");
                        onClose();
                    } else {
                        setErr("Email wasn't sent, please check logs for more details.");
                    }

                    return;
                }

                toast.status(status);
            })
            .catch((e) => {
                toast.status(e);
            })
            .finally(() => {
                setSending(false);
            });
    }

    const onTabChange = (v) => !sending && setActiveTab(v);

    return (
        <Modal style={{ height: "auto", width: "440px" }} onClose={sending ? false : onClose}>
            <div className="d5pd2">
                <div className="d5f20 d5cpri d5fwb">Re-send email</div>

                {!env.pro && (
                    <div className="d5cbox d5mt14 d5pd2 d5tc d5cnom">
                        This feature is only available in pro plan.
                        <div className="d5f20 d5mt10">
                            <a href={`${env.buylink}?ref=resendemail${info.s}`} target="_blank" className="d5link2">Buy Now ↗</a>
                        </div>
                    </div>
                )}

                {env.pro && (
                    <>
                        <div className="d5mt10 d5cnom">
                            <Tabs
                                items={[
                                    { key: 'to', label: 'To *', active: activeTab === 'to' },
                                    { key: 'cc', label: 'Cc', active: activeTab === 'cc' },
                                    { key: 'bcc', label: 'Bcc', active: activeTab === 'bcc' },
                                ]}

                                onChange={onTabChange}
                            />
                        </div>

                        <div className="d5mt10 d5cnom">
                            <div className="d5f12 d5mb6">Separate emails with a comma in case of multiple recipients</div>
                            <div style={{ display: activeTab === 'to' ? 'block' : 'none' }}>
                                <Textarea ref={toRef} disabled={sending} name="to" defaultValue={info.to} />
                                {errors.to && (<div className="d5ferr">{errors.to}</div>)}
                            </div>

                            <div style={{ display: activeTab === 'cc' ? 'block' : 'none' }}>
                                <Textarea ref={ccRef} disabled={sending} name="cc" defaultValue={info.cc} />
                                {errors.cc && (<div className="d5ferr">{errors.cc}</div>)}
                            </div>

                            <div style={{ display: activeTab === 'bcc' ? 'block' : 'none' }}>
                                <Textarea ref={bccRef} disabled={sending} name="bcc" defaultValue={info.bcc} />
                                {errors.bcc && (<div className="d5ferr">{errors.bcc}</div>)}
                            </div>
                        </div>

                        <div className="d5mt10 d5cnom">
                            <div className="d5f12 d5mb6">Subject</div>
                            <Input ref={subjectRef} disabled={sending} name="subject" defaultValue={info.subject} />
                            {errors.subject && (<div className="d5ferr">{errors.subject}</div>)}
                        </div>

                        {!!err && (
                            <div className="d5ferr d5mt10">
                                {err}
                            </div>
                        )}

                        <div className="d5mt14 d5cnom d5flex d5gap10">
                            <div className="d5grow"></div>
                            <Button size="small" type="d" disabled={sending} onClick={onClearAll}>Clear all</Button>
                            <Button size="small" type="s" disabled={sending} onClick={onSend}>{sending && <Icons.Lod />} Send</Button>
                        </div>
                    </>
                )}
            </div>
        </Modal>
    )
}

// 26 Feb 2016