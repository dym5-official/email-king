import styles from "./css/item.uniq.css";
import { providers } from "./profile-form";

import runtime from "../../../dev/runtime/runtime";

const { UI, Icons } = runtime;

const detailsMapping = {
    smtp: SMTPDetail,
    awsses: AwsSESDetail,
}

export default function Item({ active, item, onStatusChange, onDelete, onEdit, setAuthorize, setSetupSender, settings, setCredsVerify }) {
    const provider = providers[item.provider];
    const Details = detailsMapping[item.provider];

    return (
        <div className={`${styles.item} ${active ? styles.active : ''} d5cnom`}>
            <div className="d5acflex d5gap6 d5pd2tbh">
                <div className="d5grow d5cpri d5f20"><strong>{item.name}</strong></div>
                <img className="d5block d5dim" title={provider.label} src={`${window.VARS.url}/assets/${provider.icon}`.replace("./", "")} style={{ height: "12px" }} />
                <div className="d5lh1 d5f12 d5dim">{provider.label}</div>
            </div>

            <ShowSendAs
                item={item}
                setAuthorize={setAuthorize}
                setCredsVerify={setCredsVerify}
                setSetupSender={setSetupSender}
                settings={settings}
            />

            <div className="d5pd2tb1h">
                {!!Details && (
                    <div className="d5mb10">
                        <Details styles={styles} item={item} />
                    </div>
                )}

                <div>
                    <div className="d5acflex d5lh1 d5nosel d5gap10">
                        <UI.Switch checked={active} onChange={onStatusChange} loading={item.loading === "status"} value={item._id} /> {active ? <span className="d5csec">Default</span> : "Default"}
                        <div className={`d5grow d5tr ${styles.actions}`}>
                            <div className="d5acflex d5ib d5f14">
                                <span className="d5cred d5clk" onClick={() => onDelete(item._id)}><Icons.Fa i="trash" /> Delete</span>
                                &nbsp;&nbsp;
                                <span className="d5cpri d5clk" onClick={() => onEdit(item)}><Icons.Fa i="pencil" /> Edit</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    )
}

function ShowSendAs({ item, setAuthorize, setSetupSender, settings, setCredsVerify }) {
    const needsAuth = !!item.auth_url;
    const needsCredsVerify = item.recverify && !item.verified;
    const needsSenderSetup  = item.provider === "awsses" && (!item.fromname || !item.fromemail);
    const isReady = !needsAuth && !needsCredsVerify && !needsSenderSetup;
    const csender = item.customsender === "1";

    const fromname = csender ? item.fromname : settings.sendername;
    const fromemail = csender ? item.fromemail : settings.senderemail;

    return (
        <div className={`d5f12 d5lh1 d5pd2tbh ${styles.siw}`}>
            {needsAuth && (<div className="d5cred"><a className="d5clk" onClick={() => setAuthorize(item)}><Icons.Fa i="times" /> This profile has not yet been authorized, click here to authorize.</a></div>)}
            {needsCredsVerify && (<div className="d5cred"><a className="d5clk" onClick={() => setCredsVerify(item)}><Icons.Fa i="times" /> {item.verification.title}</a></div>)}
            {!needsCredsVerify && needsSenderSetup && (<div className="d5cred"><a className="d5clk" onClick={() => setSetupSender(item)}><Icons.Fa i="times" /> A sender needs to be configured first, click here to configure.</a></div>)}

            {isReady && (
                <div className="d5acflex">
                    <div className="d5grow"><span className="d5dim">Sending as </span>{fromname}<span className="d5dim"> from </span>{fromemail}</div>
                    <div><a className={`${styles.actions} d5clk d5cpri`} onClick={() => setSetupSender(item)}><Icons.Fa i="user-gear" /> Configure</a></div>
                </div>
            )}
        </div>
    )
}

function AwsSESDetail({ item }) {
    return (
        <div className={styles.info} style={{ paddingBottom: "4px" }}>
            <div>
                <div className={`${styles.infolabel} d5csec`}>REGION</div>
                <div className="d5dim d5f14">{item.region_name}</div>
            </div>
        </div>
    )
}

function SMTPDetail({ item }) {
    return (
        <div className={styles.info}>
            <div className={styles.host}>
                <div className={`${styles.infolabel} d5csec`}>HOST</div>
                <div className="d5dim d5f14">{item.host}</div>
            </div>
            <div>
                <div className={`${styles.infolabel} d5csec`}>PORT</div>
                <div className="d5dim d5f14">{item.port}</div>
            </div>
            <div>
                <div className={`${styles.infolabel} d5csec`}>ENC</div>
                <div className="d5dim d5f14">{item.enc.toUpperCase()}</div>
            </div>
            <div>
                <div className={`${styles.infolabel} d5csec`}>AUTO TLS</div>
                <div className="d5dim">{item.autotls === '1' ? <span className="d5cpri"><Icons.Fa i="check" /></span> : <span className="d5cred"><Icons.Fa i="times" /></span>}</div>
            </div>
            <div>
                <div className={`${styles.infolabel} d5csec`}>AUTH</div>
                <div className="d5dim">{item.auth === '1' ? <span className="d5cpri"><Icons.Fa i="check" /></span> : <span className="d5cred"><Icons.Fa i="times" /></span>}</div>
            </div>
        </div>
    )
}