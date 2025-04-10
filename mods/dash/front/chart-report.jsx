import runtime from "../../../dev/runtime/runtime";
import DatePicker from "./date-picker";

import styles from "./chart.uniq.css"

const { Fragment, useRef, useEffect, useState, UI, Icons: { Fa }, wirec } = runtime;

const formatLinePoints = (arr) => {
    let result = '';

    for (let i = 0; i < arr.length; i++) {
        result += arr[i];
        if (i % 2 === 0 && i !== arr.length - 1) {
            result += ',';
        } else if (i % 2 !== 0 && i !== arr.length - 1) {
            result += ' ';
        }
    }

    return result;
}

function Chart({ options, wrapper, height: rawHeight }) {
    const [rawWidth, setRawWidth] = useState(0);

    useEffect(() => {
        const setWidth = () => {
            setRawWidth(wrapper.current.clientWidth);
        }

        setTimeout(setWidth, 20);
        setTimeout(setWidth, 150);
    }, []);

    const { data, config } = options;

    const allNums = data.reduce((acc, curr) => {
        return [...acc, ...curr.figs];
    }, []);

    const max = Math.max(...allNums);
    const width = rawWidth - 32;
    const height = rawHeight - 32;
    const ncols = data.length;
    const colwidth = width / ncols;
    const circleSize = 3;
    const pointsHeightPadding = 16;
    const calcHeight = height - pointsHeightPadding;
    const halfCircleSize = circleSize / 2;
    const lines = [];
    const linesSpace = 32;
    const numberOfLines = Math.ceil(calcHeight / linesSpace);

    return (
        <svg width={rawWidth} height={rawHeight} xmlns="http://www.w3.org/2000/svg">
            {new Array(numberOfLines).fill(0).map((_, i) => {
                const y = height - ((i + 1) * linesSpace);

                return (
                    <line
                        x1="2"
                        key={i}
                        y1={y}
                        x2={rawWidth}
                        y2={y}
                        opacity={0.2}
                        strokeWidth={0.45}
                        stroke="#ffffff"
                        strokeDasharray="3,3"
                    />
                )
            })}

            {new Array(ncols).fill(0).map((_, i) => {
                const x = ((i + 1) * colwidth) - (colwidth / 2) - halfCircleSize;

                return (
                    <line
                        key={i}
                        x1={x}
                        x2={x}
                        y1={0}
                        y2={height}
                        stroke="#ffffff"
                        strokeDasharray="3,3"
                        opacity={0.15}
                    />
                )
            })}

            {new Array(ncols).fill(0).map((_, i) => {
                const x = (colwidth / 2) + ((i + 1) * colwidth) - (colwidth / 2) - halfCircleSize;

                return (
                    <line
                        key={i}
                        x1={x}
                        x2={x}
                        y1={0}
                        y2={height}
                        stroke="#ffffff"
                        strokeDasharray="3,3"
                        opacity={0.15}
                    />
                )
            })}

            <line x1="1" y1="0" x2="1" y2={height} strokeWidth={1} stroke="#a55f3d" />
            <line x1="2" y1={height - 1} x2={rawWidth} y2={height - 1} strokeWidth={1} stroke="#a55f3d" />

            {max > 0 && (
                <>
                    {data.map((point, i) => {
                        const index = i + 1;

                        return (
                            <Fragment key={index}>
                                {point.figs.map((value, j) => {
                                    if (typeof lines[j] === "undefined") {
                                        lines[j] = [];
                                    }

                                    const cx = (index * colwidth) - (colwidth / 2) - halfCircleSize;
                                    const cy = (calcHeight - ((value / max) * calcHeight)) - 1 + pointsHeightPadding;

                                    lines[j].push(cx, cy);

                                    return (
                                        <circle
                                            key={j}
                                            cx={cx}
                                            cy={cy}
                                            r={circleSize}
                                            fill={config[j].color}
                                        />
                                    )
                                })}
                            </Fragment>
                        )
                    })}
                </>
            )}

            {lines.map((points, i) => {
                return (
                    <polyline
                        opacity={0.4}
                        key={i}
                        fill="none"
                        stroke={config[i].color}
                        strokeWidth={1}
                        points={formatLinePoints(points)}
                    />
                )
            })}

            {data.map((item, i) => {
                const x = ((i + 1) * colwidth) - (colwidth / 2) - halfCircleSize;

                return (
                    <text
                        x={x}
                        y={height + 16}
                        fontSize={10}
                        key={item.label}
                        fill="#a55f3d"
                        textAnchor="middle"
                        dominantBaseline="middle"
                    >{item.label}</text>
                )
            })}
        </svg>
    )
}

const options = {
    // data: [
    //     { label: "01", figs: [20, 3, 23] },
    // ],

    config: [
        { label: "Sent", color: "green" },
        { label: "Failed", color: "#f14848d5" },
        { label: "Total", color: "#426191" },
    ]
}

export default function ChartReport({ chart: initChart }) {
    const wrapper = useRef();
    
    const [openPicker, setOpenPicker] = wirec.state.init("open_datepicker", useState, false);
    const [customChart] = wirec.state.init("custom_chart", useState, false);

    const chart = customChart ? customChart : initChart;

    const { from, to } = chart;

    return (
        <>
            {!!openPicker && <DatePicker pre={{from, to}} />}

            <div className={`d5ball d5mt30 ${styles.chartw}`}>
                <div className="d5pd3">
                    <div ref={wrapper}>
                        <Chart
                            options={{ ...options, data: chart.stat }}
                            height={280}
                            wrapper={wrapper}
                        />
                    </div>

                    <div className="d5flex d5f12 d5lh1 d5cnom d5gap20">
                        <div className="d5flex d5gap20 d5grow">
                            {options.config.map((conf) => {
                                return (
                                    <div className="d5dim d5acflex d5gap6" key={conf.label}>
                                        <div style={{ backgroundColor: conf.color }} className={styles.color}></div>
                                        {conf.label}
                                    </div>
                                )
                            })}
                        </div>
                        <div className="d5acflex d5gap10">
                            <div className="d5cbox d5f12 d5acflex d5gap6 d5clk" style={{padding: "4px 12px"}} onClick={() => setOpenPicker(true)}>
                                <Fa i="calendar" /> {chart.from} - {chart.to}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    )
}