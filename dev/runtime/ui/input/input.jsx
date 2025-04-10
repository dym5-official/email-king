import { forwardRef } from "react";
import styles from "./input.uniq.css";

const Input = forwardRef(function Input({ className, borderless = false, size = "", type="text", ...rest }, ref) {
    const classNames = [
        styles.input,
        styles.height,
        className,
        size ? styles[`size_${size}`] : false,
        borderless ? styles.borderless : false,
        "d5_ioa_el"
    ].filter(Boolean).join(" ");

    return (
        <input ref={ref} className={classNames} type={type} {...rest} />
    )
})

export default Input;