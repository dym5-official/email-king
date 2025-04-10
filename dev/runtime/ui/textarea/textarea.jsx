import { forwardRef } from "react";

import inputStyles from "../input/input.uniq.css";
import styles from "./textarea.uniq.css";

const Textarea = forwardRef(function Textarea({ children, value, className, resize=false, ...rest }, ref){
    const content = value ? value : children;

    const classNames = [
        inputStyles.input,
        styles.textarea,
        resize ? '' : styles.noresize,
        className
    ].filter(Boolean).join(" ");
    
    return (
        <textarea className={classNames} ref={ref} {...rest}>{content}</textarea>
    )
});

export default Textarea;