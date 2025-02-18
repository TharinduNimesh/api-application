export interface ChartDataset {
  label: string;
  data: number[];
  fill: boolean;
  borderColor: string;
  backgroundColor: string;
  tension: number;
}

export interface ChartData {
  labels: string[];
  datasets: ChartDataset[];
}

export interface ChartOptions {
  plugins: {
    legend: {
      display: boolean;
    };
  };
  scales: {
    y: {
      beginAtZero: boolean;
    };
  };
}
