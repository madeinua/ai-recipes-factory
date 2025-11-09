import {BrowserRouter, Routes, Route} from 'react-router-dom';
import HomePage from '../pages/HomePage';
import RequestDetailPage from '../pages/RequestDetailPage';

function App() {
    return (
        <BrowserRouter>
            <Routes>
                <Route path="/" element={<HomePage/>}/>
                <Route path="/request/:id" element={<RequestDetailPage/>}/>
            </Routes>
        </BrowserRouter>
    );
}

export default App;
