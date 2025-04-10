import styles from "./button.uniq.css";

export default function Button({ className, disabled = false, type = "", buttonType = "button", ml = false, mr = false, size = false, children, href = null, ...rest }) {
    const classNames = [
        styles.btn,
        type.indexOf("d") !== -1 ? styles.danger : false,
        type.indexOf("s") !== -1 ? styles.sec : false,
        type.indexOf("i") !== -1 ? styles.icononly : false,
        type.indexOf("l") !== -1 ? styles.noborder : false,
        ml ? styles["ml"] : false,
        mr ? styles["mr"] : false,
        className,
        disabled ? styles.dis : false,
        size ? styles[size] : false,
        href ? styles.link : false,
        "d5_ioa_el"
    ].filter(Boolean).join(" ");

    if ( href ) {
        return (
            <a disabled={disabled} href={href} className={classNames} {...rest}>{children}</a>
        )
    }

    return (
        <button disabled={disabled} className={classNames} type={buttonType} {...rest}>{children}</button>
    )
}