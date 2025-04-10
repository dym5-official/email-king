import { forwardRef } from "react";

import styles from "./switch.uniq.css";

const Input = forwardRef(function Switch({ onChange = () => null, loading = false, _style={}, className = '', ...rest }, ref) {
    const classNames = [className, styles.switch, loading ? styles.loading : ''].filter(Boolean);

    return (
        <label className={classNames.join(" ")} style={{..._style}}>
            <input type="checkbox" onChange={onChange} {...rest} ref={ref} /><span></span>
        </label>
    )
});

export default Input;