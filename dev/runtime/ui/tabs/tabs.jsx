import styles from "./tabs.uniq.css";

export default function Tabs({ items = [], equal = true, className = '', onChange = () => null }) {
    const classNames = [
        styles.tabs,
        equal ? styles.equal : false,
        equal ? false : "d5clear",
        className,
    ].filter(Boolean).join(" ");

    return (
        <div className={classNames}>
            {items.map((item) => {
                const className = item.active ? styles.active : null;

                return (
                    <a className={className} key={item.key} onClick={() => onChange(item.key)}>{item.label}</a>
                )
            })}
        </div>
    )
}