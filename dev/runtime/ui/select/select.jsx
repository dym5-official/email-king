import { forwardRef } from "react";
import inputStyles from "../input/input.uniq.css";
import styles from "./select.uniq.css";

const Select = forwardRef(function Select({ className, borderless = false, children, ...rest }, ref) {
    const classNames = [
        inputStyles.input,
        inputStyles.height,
        styles.select,
        className,
        borderless ? styles.borderless : false,
        "d5_ioa_el"
    ].filter(Boolean).join(" ");

    return (
        <select className={classNames} ref={ref} {...rest}>
            {children}
        </select>
    )
});

export default Select;