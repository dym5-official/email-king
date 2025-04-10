import styles from "./css/form.uniq.css";

// Forms
import GoogleForm from "./providers/google/google-form";
import SMTPForm from "./providers/smtp/smtp-form";
import BrevoForm from "./providers/brevo/brevo-form";
import SendgridForm from "./providers/sendgrid/sendgrid-form";
import PostmarkForm from "./providers/postmark/postmark-form";
import SendLayerForm from "./providers/sendlayer/sendlayer-form";
import MailgunForm from "./providers/mailgun/mailgun-form";
import SMTPComForm from "./providers/smtpcom/smtpcom-form";
import MailjetForm from "./providers/mailjet/mailjet-form";
import MailerSendForm from "./providers/mailersend/mailersend-form";
import MailtrapForm from "./providers/mailtrap/mailtrap-form";
import SparkPostForm from "./providers/sparkpost/sparkpost-form";
import ProForm from "./providers/pro/pro-form";

// Icons
import aswsesIcon from "./providers/awsses/awsses.svg";
import googleIcon from "./providers/google/google.svg";
import outlookIcon from "./providers/outlook/outlook.svg";
import smtpIcon from "./providers/smtp/smtp.svg";
import zohoIcon from "./providers/zoho/zoho.svg";
import brevoIcon from "./providers/brevo/brevo.svg";
import sendgridIcon from "./providers/sendgrid/sendgrid.svg";
import postmarkIcon from "./providers/postmark/postmark.svg";
import sendLayerIcon from "./providers/sendlayer/sendlayer.svg";
import mailgunIcon from "./providers/mailgun/mailgun.svg";
import smtpComIcon from "./providers/smtpcom/smtpcom.svg";
import mailjetIcon from "./providers/mailjet/mailjet.svg";
import mailerSendIcon from "./providers/mailersend/mailersend.svg";
import mailtrapIcon from "./providers/mailtrap/mailtrap.svg";
import sparkpostIcon from "./providers/sparkpost/sparkpost.svg";

import env from "../../../dev/src/env";
import runtime from "../../../dev/runtime/runtime";

const {
    UI,
    useState,
    useEffect,
    useRef,
    Icons,
    toast,
    utils,
    api
} = runtime;

let AwsSESFrom_ = typeof AwsSESFrom === "undefined" ? false : AwsSESFrom;
let OutlookForm_ = typeof OutlookForm === "undefined" ? false : OutlookForm;
let ZohoForm_ = typeof ZohoForm === "undefined" ? false : ZohoForm;

if (!env.pro) {
    AwsSESFrom_ = ProForm;
    OutlookForm_ = ProForm;
    ZohoForm_ = ProForm;
}

export const providers = {
    smtp: {
        type: "smtp",
        form: SMTPForm,
        label: "SMTP",
        icon: smtpIcon,
        pro: false,
    },
    google: {
        type: "google",
        form: GoogleForm,
        label: "Google / Gmail",
        icon: googleIcon,
        pro: false,
    },
    brevo: {
        type: "brevo",
        form: BrevoForm,
        label: "Brevo",
        icon: brevoIcon,
        pro: false,
    },
    sendgrid: {
        type: "sendgrid",
        form: SendgridForm,
        label: "Sendgrid",
        icon: sendgridIcon,
        pro: false,
    },
    postmark: {
        type: "postmark",
        form: PostmarkForm,
        label: "Postmark",
        icon: postmarkIcon,
        pro: false,
    },
    sendlayer: {
        type: "sendlayer",
        form: SendLayerForm,
        label: "SendLayer",
        icon: sendLayerIcon,
        pro: false,
    },
    mailgun: {
        type: "mailgun",
        form: MailgunForm,
        label: "Mailgun",
        icon: mailgunIcon,
        pro: false,
    },
    smtpcom: {
        type: "smtpcom",
        form: SMTPComForm,
        label: "SMTP.com",
        icon: smtpComIcon,
        pro: false,
    },
    mailjet: {
        type: "mailjet",
        form: MailjetForm,
        label: "Mailjet",
        icon: mailjetIcon,
        pro: false,
    },
    mailersend: {
        type: "mailersend",
        form: MailerSendForm,
        label: "MailerSend",
        icon: mailerSendIcon,
        pro: false,
    },
    mailtrap: {
        type: "mailtrap",
        form: MailtrapForm,
        label: "Mailtrap",
        icon: mailtrapIcon,
        pro: false,
    },
    sparkpost: {
        type: "sparkpost",
        form: SparkPostForm,
        label: "SparkPost",
        icon: sparkpostIcon,
        pro: false,
    },
    outlook: {
        type: "outlook",
        form: OutlookForm_,
        label: "Outlook",
        icon: outlookIcon,
        pro: true,
    },
    awsses: {
        type: "awsses",
        form: AwsSESFrom_,
        label: "Amazon SES",
        icon: aswsesIcon,
        pro: true,
    },
    zoho: {
        type: "zoho",
        form: ZohoForm_,
        label: "Zoho",
        icon: zohoIcon,
        pro: true,
    },
}

export default function ProfileForm({ editData, onAdd, onUpdate, setEditData, setAuthorize, setCredsVerify }) {
    const [errors, setErrors] = useState({});
    const [loading, setLoading] = useState(false);
    const [provider, setProvider] = useState(false);

    const form = useRef();
    const isEdit = !!editData._id;

    const resetForm = () => {
        form.current.reset();
        setEditData({});
        setErrors({});
        setProvider(false);
    }

    const handleSubmit = (e) => {
        e.preventDefault();

        setLoading(true);
        setErrors({});

        const data = utils.formdata(form.current, {
            action: isEdit ? "update" : "create",
            id: editData._id || "",
            autotls: "0",
            auth: "0",
            customsender: "0",
            provider: provider?.type || "",
        });

        api.post(['default', 'manage_smtp_profiles'], data)
            .then(({ data: { status, payload } }) => {
                if (status === 422) {
                    return setErrors({ ...payload });
                }

                if (status === 200) {
                    if (payload.auth_url) {
                        setAuthorize(payload);
                    } else if (payload.recverify && !payload.verified) {
                        setCredsVerify(payload, true);
                    }

                    setEditData(payload, false);

                    if (isEdit) {
                        onUpdate(editData._id, payload);
                        toast.success(`Updated successfully.`)
                    } else {
                        onAdd(payload);
                        toast.success(`Created successfully.`)
                    }

                    return;
                }

                toast.status(status);
            })
            .catch((e) => toast.status(e))
            .finally(() => setLoading(false))

        return false;
    }

    const handleGoBack = () => {
        if (!isEdit) {
            setProvider(false);
            setErrors({});
        }
    }

    useEffect(() => {
        setErrors({});
        setLoading(false);
        setProvider(providers[editData?.provider || "x"] || false);
    }, [editData]);

    return (
        <>
            <div className={`${styles.formw} d5cnom`}>
                <form onSubmit={handleSubmit} ref={form}>
                    <div className="d5tc">
                        <img src={`${window.VARS.url}/mods/smtp/icon.svg`} style={{ height: '48px' }} />
                    </div>

                    <div className="d5tc d5f20 d5cpri">
                        <strong>{isEdit ? "Update" : "Set Up"} Email Provider</strong>
                    </div>

                    <div>
                        <div className={styles.label}>NAME<sup className="d5req">*</sup></div>
                        <UI.Input disabled={loading} size="big" defaultValue={editData?.name || ""} name="name" autoComplete="off" />
                        {!!errors.name && <div className="d5ferr">{errors.name}</div>}
                    </div>

                    {!provider && (
                        <div className={`${styles.providers}`}>
                            {Object.values(providers).map((provider) => {
                                return (
                                    <div key={provider.type} className="d5clk" onClick={() => setProvider(provider)}>
                                        <img src={`${window.VARS.url}/assets/${provider.icon}`.replace("./", "")} />
                                        <div className="d5f12 d5mt10 d5tc">{provider.label}</div>
                                        {!env.pro && provider.pro && (
                                            <div className={styles.protag}>PRO</div>
                                        )}
                                    </div>
                                )
                            })}
                        </div>
                    )}

                    {!!provider && (
                        <>
                            <div className="d5acflex d5gap10">
                                <UI.Button type="i" onClick={handleGoBack}>{isEdit ? null : <Icons.Fa i="chevron-left" />}</UI.Button>
                                <img style={{height:"26px",width:"auto"}} src={`${window.VARS.url}/assets/${provider.icon}`.replace("./", "")} />
                                <div className="d5f14">{provider.label}</div>
                            </div>

                            <provider.form
                                styles={styles}
                                errors={errors}
                                editData={editData}
                                loading={loading}
                                type={provider.type}
                            />

                            {(!provider.pro || (provider.pro && env.pro)) && (
                                <div>
                                    <div className="d5flex d5gap10">
                                        <UI.Button disabled={loading} className="d5grow" type="s" buttonType="submit" size="big">
                                            {loading ? <Icons.Lod /> : <Icons.Fa i="check" />}
                                            &nbsp;&nbsp;
                                            {isEdit ? "Update" : "Create"}
                                        </UI.Button>

                                        <UI.Button type="d" size="big" onClick={resetForm}>
                                            Exit <Icons.Fa i="arrow-right" />
                                        </UI.Button>
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </form>
            </div>
        </>
    )
}

export { styles };