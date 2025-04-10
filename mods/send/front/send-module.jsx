import env from "../../../dev/src/env";
import styles from "./send.uniq.css";

export default function ModEmailSend() {
    return (
        <>
            <div className={`${styles.modwrp} d5clear`}>
                <div className={`${styles.formside} d5cflex`}>
                    <div className="d5tc d5pd2">
                        <img src={`${window.VARS.url}/mods/send/icon.svg`} alt="" style={{ height: "60px", width: "auto" }} />
                        <div className="d5f20 d5mt10">
                            <span className="d5cpri">Send</span> <span className="d5csec">Email</span>
                        </div>
                        <div className="d5cnom d5dim d5mt6 d5f14">
                            No need to go anywhere else to send simple emails.
                        </div>
                    </div>
                </div>

                <div className={`${styles.modcont}${env.pro ? '' : ' d5cflex'}`}>
                    {!env.pro && (
                        <div className="d5cnom d5tc">
                            This feature is only available in pro plan.
                            <div className="d5f20 d5mt10">
                                <a href={`${env.buylink}?ref=sendemail`} target="_blank" className="d5link2">Buy Now ↗</a>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </>
    )
}