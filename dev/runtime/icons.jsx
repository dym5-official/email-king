import "@fortawesome/fontawesome-free/css/all.css";

const Fa = ({ i, className = '', style = {}, size = false, ...rest }) => {
    const icon = [i.indexOf(' ') !== -1 ? i : `fa fa-${i}`, className];
    const styles = { ...style };

    if (size) {
        styles.fontSize = size;
    }

    return <i className={icon.filter(Boolean).join(' ')} style={{...styles}} {...rest} />
}

const Ms = () => <i>..ms..</i>

export const Lod = (props) => {
    return <Fa i="fa fa-spin fa-circle-notch" {...(props || {})} />
}

const Icons = {
    Fa,
    Ms,
    Lod,
}

export default Icons;