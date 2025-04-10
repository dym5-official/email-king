import { useState, useEffect } from "react";

import Icons from "../../icons";
import Button from "../button/button";
import styles from "./modal.uniq.css";

export default function Modal({ className = '', onClose = null, loading = false, children, ...rest }) {
    const [anim, setAnim] = useState(styles.zoomin);

    useEffect(() => {
        setTimeout(setAnim, 800, "");
    },[]);

    const classNames = [
        styles.modalc,
        className,
        anim
    ].filter(Boolean).join(" ");

    return (
        <>
            <div className={styles.modalw} />

            <div className={styles.modal}>
                <div className={classNames} {...rest}>
                    {!loading && typeof onClose === "function" && (<a className={`d5cred d5clk ${styles.close}`} onClick={onClose}><Icons.Fa i="close" /></a>)}
                    {children}
                </div>
            </div>
        </>
    )
}

export function Confirm({ message = 'Are you sure to delete?', onConfirm = () => null, onCancel = () => null, progress = false, width = "260px", children }) {
    const { Lod } = Icons;

    return (
        <Modal className="d5pd2" style={{width, height: "auto"}}>
            {!!message && <div className={styles.cnfmsg}>{message}</div>}
            {!!children && <div>{children}</div>}
            <div className={styles.cnfbtns}>
                <Button onClick={onConfirm} size="small" disabled={progress}>{progress && <Lod />} Confirm</Button>
                <Button onClick={() => !progress && onCancel()} size="small" disabled={progress} type="d">Cancel</Button>
            </div>
        </Modal>
    )
}