import "./bootstrap";
import Sortable from "sortablejs";
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import './lot-map';

window.Livewire = Livewire;
window.Alpine = Alpine;
window.Sortable = Sortable;



Livewire.start();
