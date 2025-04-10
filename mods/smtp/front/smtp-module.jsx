import Item from "./item";
import ProfileForm from "./profile-form";
import TestEmail from "./test-email";
import Authorize from "./authorize";
import SenderInfo from "./sender-info";
import VerifyCreds from "./verify-creds";

import styles from "./css/smtp.uniq.css";
import runtime from "../../../dev/runtime/runtime";

const {
    UI,
    useState,
    useRef,
    useEffect,
    Icons,
    toast,
    api,
    wirec
} = runtime;

export default function ModEmailSMTP({ data: { profiles, admin_email, settings } }) {
    const [items, setItems] = useState([...profiles]);
    const [del, setDel] = useState(false);
    const [delLod, setDelLod] = useState(false);
    const [confirmStatusChange, setConfirmStatusChange] = useState(false);
    const [editData, setEditData] = useState({});
    const [flashKey, setFlashKey] = useState(Math.random());
    const [testEmail, setTestEmail] = useState(false);
    const [theSettings] = wirec.state.init("settings", useState, { ...(settings || {}) });

    const [authorize, setAuthorize] = useState(false);
    const [setupSender, setSetupSender] = useState(false);
    const [credsVerify, setCredsVerify] = useState(false);

    const credsItemRef = useRef();
    const credsFromFrom = useRef();

    const setTheCredsVerify = (item, fromForm = false) => {
        if (item) {
            credsItemRef.current = {...item};
            credsFromFrom.current = fromForm;
        }

        setCredsVerify(item);
    }

    useEffect(() => {
        const item = credsItemRef.current;

        if (item && ["awsses", "smtpcom"].indexOf(item.provider) !== -1) {
            const newItem = items.find((x) => x._id === item._id);

            if (newItem && newItem.verified) {
                setTimeout(setSetupSender, 500, newItem);
            }

            if (credsFromFrom.current) {
                credsFromFrom.current = false;
            } else {
                credsItemRef.current = false;
            }
        }

        setTimeout(wirec.put, 500, "profiles", items);
    }, [items]);

    const onAdd = (data) => setItems([{ ...data }, ...items]);

    const patchProfiles = (special = {}, all = {}) => {
        setItems(items.map((item) => {
            if (special[item._id]) {
                return { ...item, ...special[item._id] };
            }

            return { ...item, ...all };
        }));
    }

    const onStatusChange = (e, confirmed = false) => {
        if (!confirmed) {
            return setConfirmStatusChange(e);
        }

        const id = e.target.value;

        patchProfiles({ [id]: { loading: 'status' } });

        api.post(['default', 'manage_smtp_profiles'], { action: 'enable', status: !e.target.checked, id: e.target.value })
            .then(({ data: { status } }) => {
                if (status === 200) {
                    patchProfiles({
                        [id]: {
                            loading: false,
                            default: e.target.checked ? "0" : "1"
                        }
                    }, {
                        default: "0"
                    });

                    return;
                }

                toast.status(status);
            })
            .catch((e) => {
                toast.status(e);
                patchProfiles({ [id]: { loading: false } });
            });
    }

    const onDelete = (id, confirmed = false) => {
        if (!confirmed) {
            return setDel(id);
        }

        api.post(['default', 'manage_smtp_profiles'], { id, action: 'delete' })
            .then(({ data: { status } }) => {
                if (status === 200) {
                    if (editData._id === id) {
                        setEditData({});
                    }

                    return setItems(items.filter((item) => item._id !== id));
                }

                toast.status(status);
            })
            .catch((e) => toast.status(e))
            .finally(() => {
                setDel(false);
                setDelLod(false);
            });
    }

    const onEdit = (data, flash = true) => {
        data.__flash = flash;
        setEditData(data);

        if ( flash ) {
            setFlashKey(Math.random());
        }
    }

    const onUpdate = (id, data) => patchProfiles({ [id]: data });

    const checked = confirmStatusChange?.target?.checked || false;
    const pKey = editData?._id || 0;

    return (
        <>
            <div className={`${styles.modwrp} d5clear`}>
                <div className={`${styles.formside} d5acflex`}>
                    {!!editData._id && <div key={flashKey} className={`${styles.flashbg} ${editData.__flash === false ? '' : 'd5flash'}`}></div>}
                    <ProfileForm
                        onAdd={onAdd}
                        onUpdate={onUpdate}
                        editData={editData}
                        setEditData={onEdit}
                        setAuthorize={setAuthorize}
                        setCredsVerify={setTheCredsVerify}
                        key={pKey}
                    />
                </div>

                <div className={styles.modcont}>
                    <div className={`${styles.bar} d5cnom d5pd`}>
                        <a className="d5csec d5clk" onClick={() => setTestEmail(true)}><Icons.Fa i="check-double" /> Test</a>
                    </div>
                    <div className={styles.itemsw}>
                        <div className={styles.items}>
                            {items.length === 0 && (
                                <div className="d5cnom d5tc d5dim">
                                    <div><Icons.Fa style={{ fontSize: "36px" }} i="trash-can" /></div>
                                    <div className="d5f20 d5mt10">No SMTP profiles.</div>
                                </div>
                            )}

                            {items.map((item) => {
                                return (
                                    <Item
                                        item={item}
                                        key={item._id}
                                        active={item.default === "1"}
                                        onStatusChange={onStatusChange}
                                        onDelete={onDelete}
                                        onEdit={onEdit}
                                        setSetupSender={setSetupSender}
                                        setAuthorize={setAuthorize}
                                        setCredsVerify={setTheCredsVerify}
                                        settings={theSettings}
                                    />
                                )
                            })}
                        </div>
                    </div>
                </div>
            </div>

            {!!del && (
                <UI.Confirm message={false} onCancel={() => setDel(false)} progress={delLod} onConfirm={() => {
                    setDelLod(true);
                    onDelete(del, true);
                }}>
                    <div className="d5cnom d5tc">
                        <div>Sure to deleted?</div>
                        <p className="d5f12 d5cred">Deleting this might cause email sending issues.</p>
                    </div>
                </UI.Confirm>
            )}

            {!!confirmStatusChange && (
                <UI.Confirm width="300px" message={false} onCancel={() => setConfirmStatusChange(false)} onConfirm={() => {
                    setConfirmStatusChange(false);
                    onStatusChange(confirmStatusChange, true);
                }}>
                    <div className="d5cnom d5tc">
                        <div>Sure to {checked ? "make default" : "remove default"}?</div>
                        {checked && (<p className="d5f12 d5cred">Making this default will disable another default SMTP profile.</p>)}
                        {!checked && (<p className="d5f12 d5cred">Removing this as the default might cause email sending issues.</p>)}
                    </div>
                </UI.Confirm>
            )}

            {!!credsVerify && <VerifyCreds item={credsVerify} setCredsVerify={setTheCredsVerify} onUpdate={onUpdate} />}
            {!!authorize && <Authorize item={authorize} setAuthorize={setAuthorize} onUpdate={onUpdate} />}
            {!!setupSender && <SenderInfo item={setupSender} settings={theSettings} setSetupSender={setSetupSender} onUpdate={onUpdate} />}
            {testEmail && <TestEmail admin_email={admin_email} profiles={items} onClose={() => setTestEmail(false)} />}
        </>
    )
}