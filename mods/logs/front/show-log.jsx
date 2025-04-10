import env from "../../../dev/src/env";
import runtime from "../../../dev/runtime/runtime";

const {
    useState,
    useEffect,
    useRef,
    Icons,
    api
} = runtime;

export default function ShowLog({ setShow, log, c, setResendInfo }) {
    const [anim, setAnim] = useState("slideInRight");
    const timeout = useRef();

    useEffect(() => {
        timeout.current = setTimeout(setAnim, 1000, "");
    }, []);

    const close = () => {
        clearTimeout(timeout.current);
        setAnim("slideOutRight");
        setTimeout(setShow, 700, null);
    }

    return (
        <>
            <div className={`${c.showwrp} animated ${anim}`} onClick={close}></div>

            <div className={`${c.showinr} d5cnom d5f14 animated ${anim}`}>
                <div className={`d5pd d5acflex d5gap10 ${c.timelinew}`}>
                    <div className={`d5grow d5acflex d5tc ${c.timeline}`}>
                        {log.timeline.map((tl, i) => {
                            return (
                                <div key={i} title={`${tl.message}`}>
                                    {/* <div key={i} title={`${!!tl.p_name ? tl.p_name + ': ' : ''}${tl.message}`}> */}
                                    <Icons.Fa i="circle" className={tl.success ? c.green : c.red} />
                                    <div className={`${c.tltime} d5dim`}>{tl.took}</div>
                                    <div className={c.tlname}>{tl.d_p_name}</div>
                                </div>
                            )
                        })}
                    </div>
                    <div>
                        <Icons.Fa
                            i="paper-plane"
                            className={`${c.srbtn} d5clk d5cpri`}
                            onClick={() => setResendInfo({ ...log.data, _id: log._id, s: 1 })}
                            title="Re-send this email"
                        />
                    </div>
                </div>
                <div className="d5pd d5bb d"><strong className="d5csec">To:</strong> {log.data.to}</div>
                {!!log.data.cc && (<div className="d5pd d5bb d"><strong className="d5csec">Cc:</strong> {log.data.cc}</div>)}
                {!!log.data.bcc && (<div className="d5pd d5bb d"><strong className="d5csec">Bcc:</strong> {log.data.bcc}</div>)}
                <div className="d5pd d5bb d"><strong className="d5csec">Subject:</strong> {!!log?.data?.subject ? log.data.subject : '(no subject)'}</div>
                <div className="d5grow d5cflex d5rel">
                    {!env.pro && (
                        <div className="d5tc">
                            You can see email content only in pro plan.
                            <div className="d5f20 d5mt10">
                                <a href={`${env.buylink}?ref=showlog`} target="_blank" className="d5link2">Buy Now ↗</a>
                            </div>
                        </div>
                    )}

                    {env.pro && (
                        <>
                            <div className="d5abs" style={{ zIndex: 4 }}><Icons.Lod /></div>
                            <iframe className="d5abs" frameBorder={0} src={api.url(["default", "render_body", { id: log._id }])} style={{ width: "100%", height: "100%", zIndex: 5, top: 0, left: 0 }}></iframe>
                        </>
                    )}
                </div>
            </div>
        </>
    )
}