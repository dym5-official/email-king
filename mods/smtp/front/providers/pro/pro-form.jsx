import env from "../../../../../dev/src/env"

export default function ProForm({ type }) {
    return (
        <div className="d5cbox d5pd2 d5tc">
            This provider is only available in pro plan.
            <div className="d5f20 d5mt10">
                <a href={`${env.buylink}?ref=${type}`} target="_blank" className="d5link2">Buy Now ↗</a>
            </div>
        </div>
    )
}