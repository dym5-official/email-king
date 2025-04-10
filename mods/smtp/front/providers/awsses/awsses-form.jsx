import runtime from "../../../../../dev/runtime/runtime";

const { 
    UI,
    Icons,
    useState
} = runtime;

const regions = [
    { key: 'af-south-1', name: 'Africa (Cape Town)' },
    { key: 'ap-northeast-1', name: 'Asia Pacific (Tokyo)' },
    { key: 'ap-northeast-2', name: 'Asia Pacific (Seoul)' },
    { key: 'ap-northeast-3', name: 'Asia Pacific (Osaka)' },
    { key: 'ap-south-1', name: 'Asia Pacific (Mumbai)' },
    { key: 'ap-southeast-1', name: 'Asia Pacific (Singapore)' },
    { key: 'ap-southeast-2', name: 'Asia Pacific (Sydney)' },
    { key: 'ca-central-1', name: 'Canada (Central)' },
    { key: 'eu-central-1', name: 'EU (Frankfurt)' },
    { key: 'eu-north-1', name: 'EU (Stockholm)' },
    { key: 'eu-south-1', name: 'EU (Milan)' },
    { key: 'eu-west-1', name: 'EU (Ireland)' },
    { key: 'eu-west-2', name: 'EU (London)' },
    { key: 'eu-west-3', name: 'EU (Paris)' },
    { key: 'me-south-1', name: 'Middle East (Bahrain)' },
    { key: 'sa-east-1', name: 'South America (São Paulo)' },
    { key: 'us-east-1', name: 'US East (N. Virginia)' },
    { key: 'us-east-2', name: 'US East (Ohio)' },
    { key: 'us-gov-west-1', name: 'GovCloud (US-West)' },
    { key: 'us-west-1', name: 'US West (N. California)' },
    { key: 'us-west-2', name: 'US West (Oregon)' }
];

export default function AwsSESFrom({ styles, errors, editData, loading }) {
    const [showpwd, setShowPwd] = useState(false);

    return (
        <>
            <div>
                <div className={styles.label}>ACCESS KEY ID<sup className="d5req">*</sup></div>
                <UI.Input disabled={loading} name="accesskey" defaultValue={editData?.accesskey || ""} autoComplete="off" />
                {!!errors.accesskey && <div className="d5ferr">{errors.accesskey}</div>}
            </div>

            <div>
                <div className={styles.label}>SECRET ACESSS KEY<sup className="d5req">*</sup></div>
                <div className="d5rel">
                    <UI.Input disabled={loading} name="secretkey" className={styles.pwinput} defaultValue={editData?.secretkey || ""} type={showpwd ? "text" : "password"} autoComplete="off" />
                    <Icons.Fa i={showpwd ? "eye-slash" : "eye"} className={`${styles.pwtoggle} d5clk`} onClick={() => setShowPwd(!showpwd)} />
                </div>
                {!!errors.secretkey && <div className="d5ferr">{errors.secretkey}</div>}
            </div>

            <div>
                <div className={styles.label}>SELECT YOUR REGION<sup className="d5req">*</sup></div>
                <UI.Select name="region" defaultValue={editData.region || ""}>
                    <option value=""></option>
                    {regions.map((region) => {
                        return (
                            <option key={region.key} value={region.key}>{region.name}</option>
                        )
                    })}
                </UI.Select>
                {!!errors.region && <div className="d5ferr">{errors.region}</div>}
            </div>
        </>
    )
}