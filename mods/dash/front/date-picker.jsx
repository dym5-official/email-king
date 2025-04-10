
import styles from "./picker.uniq.css";
import runtime from "../../../dev/runtime/runtime";
import env from "../../../dev/src/env";

const { UI, useState, Icons: { Fa, Lod }, wirec, api, toast } = runtime;

const closePopup = () => wirec.state.set("open_datepicker", false);

export default function DatePicker({ pre }) {
    const [loading, setLoading] = useState(false);


    const onCancel = () => !loading && closePopup();

    return (
        <UI.Modal style={{ height: "auto", width: env.pro ? "auto" : "260px" }}>
            {!env.pro && (
                <div className="d5pd2">
                    <div className="d5tc d5cnom d5cbox d5pd1h">
                    You can customize the chart date range only in the pro plan.
                        <div className="d5mt6"><a href={`${env.buylink}?ref=chartrange`} target="_blank" className="d5link2">Buy Now ↗</a></div>
                    </div>

                    <div className="d5mt14 d5acflex">
                        <div className="d5grow d5cnom d5dim d5f20">⦾</div>
                        <UI.Button size="small" onClick={onCancel}>Ok</UI.Button>
                    </div>
                </div>
            )}
        </UI.Modal>
    )
}