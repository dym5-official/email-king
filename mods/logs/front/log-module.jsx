import ShowLog from "./show-log";
import ReSendPopup from "./re-send-popup";

import styles from "./email-log.uniq.css";

import runtime from "../../../dev/runtime/runtime";

const {
    useState,
    useRef,
    useEffect,
    UI,
    Icons,
    api
} = runtime;

export default function ModEmailLogs({ data }) {
    const [logs, setLogs] = useState({ ...data });
    const [show, setShow] = useState(null);
    const [selected, setSelected] = useState([]);
    const [loading, setLoading] = useState(false);
    const [confDel, setConfDel] = useState(false);
    const [keyword, setKeyword] = useState("");
    const [resendInfo, setResendInfo] = useState(false);

    const timeout = useRef();
    const controller = useRef(null);
    const listContainerRef = useRef();

    const isLoading = !!loading;
    const allSelected = selected.indexOf('*all') !== -1;

    useEffect(() => {
        return () => {
            clearTimeout(timeout.current);
        }
    },[]);

    const handleSelect = (e) => {
        if (isLoading) {
            return;
        }

        const id = e.target.value;

        if (id === "*all") {
            return setSelected(allSelected ? [] : ["*all"]);
        }

        if (id) {
            setSelected(selected.indexOf(id) === -1 ? [...selected, id] : selected.filter((x) => x !== id));
        }
    }

    const handleNav = (type, keyword = '') => {
        if (isLoading || ((type === "next" || type === "prev") && logs.total === 0)) {
            if ( ! ( type === "search" && loading === "search") ) {
                return;
            }
        }

        let page = (type === "next") ? logs.page + 1 : logs.page - 1;

        if (type === "prev" && logs.page === 1 && page < 1) {
            return;
        }

        if (type === "next" && page > logs.total_pages) {
            return;
        }

        if (type === "refresh" || type === "sclear") {
            setKeyword("");
            page = 1;
        }

        if ( controller.current ) {
            controller.current.abort("duplicate_search");
        }

        controller.current = new AbortController();

        setLoading(type);

        api.get(["default", "get_logs", { page, keyword }], { signal: controller.current.signal })
            .then(({ data: { status, payload } }) => {
                if (status === 200) {
                    setLogs(payload);
                    setSelected([]);

                    if ( type === "refresh" ) {
                        listContainerRef.current.scrollTo(0,0);
                    }
                }

                setLoading(false)
            })
            .catch((e) => {
                if (  palacify.axios.isCancel( e ) ) {
                    return;
                }

                setLoading(false);
            })
    }

    const handleDelete = (confirmed = false) => {
        if (loading || logs.total === 0) {
            return;
        }

        if (!confirmed) {
            return setConfDel(true);
        }

        setLoading("delete");
        setConfDel(false);

        const ids = selected.length === 0
            ? ["*all"]
            : selected[0] === "*all"
                ? logs.items.map((log) => log._id)
                : [...selected];

        api.post(['default', 'delete_logs'], { ids, page: logs.page, keyword })
            .then(({ data: { status, payload } }) => {
                if (status === 200) {
                    setLogs(payload);
                    setSelected([]);
                }
            })
            .finally(() => setLoading(false));
    }

    const clearSearch = () => {
        setKeyword("");

        if (controller.current) {
            controller.current.abort();
        }

        setLoading(false);
        setTimeout(handleNav, 100, "sclear");
    }

    const handleSearch = (e, real = false) => {
        clearTimeout(timeout.current);

        const value = e.target.value;

        setKeyword(value);
        
        if ( value.trim() === "" ) {
            clearSearch();
            return;
        }

        if ( ! real ) {
            timeout.current = setTimeout(handleSearch, 800, e, true);
            return;
        }

        handleNav("search", value);
    }
    
    const padd = new Array(40 - logs.items.length).fill(0);

    return (
        <>
            {!!resendInfo && (
                <ReSendPopup
                    info={resendInfo}
                    onClose={() => setResendInfo(false)}
                />
            )}

            <div className={styles.logwrp}>
                <div className={styles.loginr}>
                    <div className={`${styles.tools} d5cnom d5f14 d5acflex d5gap20 d5lh1`}>
                        <div className={`d5acflex d5lh1 ${(logs.total === 0) ? "d5dim2" : ""}`}>
                            <UI.Checkbox checked={allSelected} disabled={logs.total === 0 || isLoading} size="18px" value="*all" onChange={handleSelect}></UI.Checkbox>&nbsp;&nbsp;All
                        </div>
                        <div className="d5clk d5csec" onClick={() => handleNav("refresh")}>
                            <Icons.Fa i="redo" className={loading === "refresh" ? "fa-spin" : ""} /> Refresh
                        </div>
                        <div className={`d5cred ${(logs.total === 0 || isLoading) ? "d5dim2" : "d5clk"}`} style={{ minWidth: "124px" }} onClick={() => handleDelete()}>
                            {loading === "delete" ? <Icons.Lod /> : <Icons.Fa i="trash" />} Delete {selected.length === 0 ? "all" : "selected"}
                        </div>
                        <div className={`d5grow d5tr`}>
                            <div className="d5ib d5rel" style={{maxWidth: "400px"}}>
                                <UI.Input onChange={handleSearch} value={keyword} placeholder="Search.." className="d5r18i" style={{ paddingLeft: "14px", paddingRight: "14px" }} />
                                {keyword !== "" && <span className={`${styles.srhclear} d5bred d5cflex d5clk`} onClick={clearSearch}><Icons.Fa i="times" /></span>}
                            </div>
                        </div>

                        <div className={`d5acflex d5gap6 ${(logs.total === 0) ? "d5dim2" : ""}`}>
                            <div>{loading === "prev" ? <Icons.Lod /> : <Icons.Fa i="chevron-left" className="d5clk" onClick={() => handleNav("prev")} />}</div>
                            <div className="d5dim">{logs.page_label}</div>
                            <div>{loading === "next" ? <Icons.Lod /> : <Icons.Fa i="chevron-right" className="d5clk" onClick={() => handleNav("next")} />}</div>
                        </div>
                    </div>

                    <div ref={listContainerRef} className={`${styles.items} d5cnom d5f14${(logs.items.length === 0 || loading === "search") ? " d5cflex" : ""}`}>
                        {logs.items.length === 0 && loading !== "search" && loading !== "sclear" && (
                            <div className="d5cnom d5tc d5dim">
                                <div><Icons.Fa style={{ fontSize: "36px" }} i="trash-can" /></div>
                                <div className="d5f20 d5mt10">No email logs.</div>
                            </div>
                        )}
                        {(loading === "search" || loading === "sclear") && <div><Icons.Lod /></div>}

                        {logs.items.length > 0 && loading !== "search" && loading !== "sclear" && (
                            <table border={0} cellSpacing={0} cellPadding={0} className={styles.table}>
                                <tbody>
                                    {logs.items.map((log) => {
                                        let stsColor = 'sec';
                                        let icon = false;

                                        if ( log.status === "sent" ) {
                                            stsColor = "green";
                                            icon = "check";
                                        }
                                    
                                        if ( log.status === "failed" ) {
                                            stsColor = "red";
                                            icon = "times";
                                        }

                                        return (
                                            <tr key={log._id} className="d5clk" onClick={() => setShow(log)}>
                                                <td className={styles.estatus}><span className={`${styles.stsblt} d5b${stsColor}`}></span></td>
                                                <td className={styles.echeck}><UI.Checkbox size="18px" checked={allSelected ? allSelected : selected.indexOf(log._id) !== -1} value={log._id} onChange={handleSelect} /></td>
                                                <td className={styles.esubject}>{log.data.subject || "(no subject)"}</td>
                                                <td className={`${styles.eto}`}>{log.data.to}</td>
                                                <td className={`${styles.estatus1} d5c${stsColor}`}>
                                                    {!!icon && (<><Icons.Fa i={icon} />&nbsp;</>)}
                                                    {log.hstatus}
                                                </td>
                                                <td className={`${styles.etook} d5csec d5f12`} title={!!log.took ? `Took ${log.took}` : ''}>{log.took}</td>
                                                <td className={`${styles.etime}`} title={log.htime}><Icons.Fa i="clock" />&nbsp;&nbsp;{log.time_ago}</td>
                                                <td className={`${styles.eresend} d5clk d5cpri`} title={"Re-send this email"} onClick={(e) => {
                                                    e.stopPropagation();
                                                    e.preventDefault();
                                                    setResendInfo({...log.data, _id: log._id, s: 0 });
                                                }}>
                                                    <Icons.Fa i="paper-plane" />
                                                </td>
                                            </tr>
                                        )
                                    })}

                                    {padd.map((_, i) => {
                                        return (
                                            <tr key={`dfj${i}`}>
                                                <td className={styles.estatus}></td>
                                                <td className={styles.echeck}></td>
                                                <td className={styles.esubject}>&nbsp;</td>
                                                <td className={`${styles.eto}`}>&nbsp;</td>
                                                <td className={`${styles.estatus1}`}>&nbsp;</td>
                                                <td className={`${styles.etook} d5csec d5f12`}></td>
                                                <td className={`${styles.etime}`}></td>
                                                <td className={`${styles.eresend}`}></td>
                                            </tr>
                                        )
                                    })}
                                </tbody>
                            </table>
                        )}
                    </div>

                    {!!show && <ShowLog key={show?._id} c={styles} setShow={setShow} log={show} setResendInfo={setResendInfo} />}
                    {isLoading && loading !== "search" && <div className={styles.overlay} />}
                </div>
            </div>

            {confDel && (
                <UI.Confirm message={null} onCancel={() => setConfDel(false)} onConfirm={() => handleDelete(true)}>
                    <div className="d5tc">
                        <strong className="d5cred">Delete {selected.length === 0 ? "all" : "selected"} logs</strong>
                        <div className="d5f12 d5cnom d5dim d5mt6">Once the logs are deleted, they cannot be restored.</div>
                    </div>
                </UI.Confirm>
            )}
        </>
    )
}