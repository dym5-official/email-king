import { forwardRef } from "react";

import styles from "./checkbox.uniq.css";

const Checkbox = forwardRef(function Checkbox({ size = '14px', type="checkbox", mr = false, mb = false, acflex = false, round = false, children, ...rest }, ref){
    return (
        <label onClick={(e) => e.stopPropagation()} className={`${styles.cb} ${acflex ? 'd5acflex' : ''} ${round ? styles.round : ''}`}><input type={type} {...rest} ref={ref} /><span style={{width:size, height:size, marginRight: mr ? mr : null, marginBottom: mb ? mb : null}}></span>{children}</label>
    )
});

export default Checkbox;