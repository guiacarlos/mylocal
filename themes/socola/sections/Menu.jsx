import React, { useState, useEffect } from 'react';
import './Menu.css';

/**
 * Menu (Carta) Section for Socolá
 * Displays products categorized and with a clean, mobile-first design.
 */
export default function MenuGrid() {
    const [products, setProducts] = useState([]);
    const [activeCategory, setActiveCategory] = useState('Todos');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        // En un entorno ACIDE real, esto vendría de una API o de los props inyectados
        // Aquí simulamos la carga de los productos que acabamos de crear
        const fetchProducts = async () => {
            try {
                // Simulamos fetching lento para elegancia
                setLoading(true);
                // En local no podemos hacer fetch directo a STORAGE vía HTTP fácilmente sin proxy
                // Pero como ACIDE compila, usaremos datos estáticos o inyectados.
                // Para esta demo, usaremos el listado que extrajimos.
                const demoProducts = [
                    { id: 1, title: "French Toasts Clásicas", content: "Dos rebanadas de brioche bañadas en huevo, leche y canela. Con plátano, compota casera de frutos rojos.", price: "8.00", category: "Dulce", image: "https://images.unsplash.com/photo-1484723088339-fe28233e562e?q=80&w=500&auto=format&fit=crop" },
                    { id: 2, title: "Lotus French Toasts", content: "Edición 9° Aniversario. Con crema de Lotus, galleta Lotus picada y helado de vainilla.", price: "8.00", category: "Dulce", image: "https://images.unsplash.com/photo-1550617931-e17a7b70dce2?q=80&w=500&auto=format&fit=crop" },
                    { id: 3, title: "Nutella & Fresas", content: "Tortitas (2 uds) o Gofres servidos con Nutella y fresas frescas.", price: "8.00", category: "Dulce", image: "https://images.unsplash.com/photo-1567620905732-2d1ec7bb7445?q=80&w=500&auto=format&fit=crop" },
                    { id: 4, title: "Porción de Tarta", content: "Consulta el expositor para ver las variedades del día. Variedades artesanas.", price: "4.50", category: "Repostería", image: "https://images.unsplash.com/photo-1578985545062-69928b1d9587?q=80&w=500&auto=format&fit=crop" },
                    { id: 5, title: "Cheesecake", content: "Tarta de queso cremosa y artesanal con base de galleta y toque de vainilla.", price: "4.50", category: "Repostería", image: "https://images.unsplash.com/photo-1533134242443-d4fd215305ad?q=80&w=500&auto=format&fit=crop" },
                    { id: 6, title: "Cinnamon Roll", content: "El original 'Cinny Roll' glaseado, tierno y especiado.", price: "3.75", category: "Repostería", image: "https://images.unsplash.com/photo-1509365465985-25d11c17e812?q=80&w=500&auto=format&fit=crop" },
                    { id: 7, title: "Cookies", content: "Galletas horneadas super gorditas. Disponibles sabores clásicos y sin gluten.", price: "3.00", category: "Repostería", image: "https://images.unsplash.com/photo-1499636131910-6979b0d668ee?q=80&w=500&auto=format&fit=crop" }
                ];
                setProducts(demoProducts);
                setLoading(false);
            } catch (error) {
                console.error("Error cargando carta:", error);
                setLoading(false);
            }
        };

        fetchProducts();
    }, []);

    const categories = ['Todos', ...new Set(products.map(p => p.category))];
    const filteredProducts = activeCategory === 'Todos'
        ? products
        : products.filter(p => p.category === activeCategory);

    return (
        <section className="menu-socola">
            <div className="menu-header">
                <div className="container">
                    <h2 className="title-carta">Nuestra Carta</h2>
                    <p className="subtitle-carta">Hecho con masa madre, amor y tiempo.</p>

                    <div className="category-filter">
                        {categories.map(cat => (
                            <button
                                key={cat}
                                className={`filter-btn ${activeCategory === cat ? 'active' : ''}`}
                                onClick={() => setActiveCategory(cat)}
                            >
                                {cat}
                            </button>
                        ))}
                    </div>
                </div>
            </div>

            <div className="container">
                {loading ? (
                    <div className="menu-loader">Preparando delicias...</div>
                ) : (
                    <div className="menu-grid">
                        {filteredProducts.map(product => (
                            <div key={product.id} className="menu-item-card">
                                <div className="item-details">
                                    <div className="item-header">
                                        <h3>{product.title}</h3>
                                        <span className="item-price">{product.price}€</span>
                                    </div>
                                    <p className="item-description">{product.content}</p>
                                    <div className="item-tags">
                                        <span className="tag-natural">100% Natural</span>
                                    </div>
                                </div>
                                {product.image && (
                                    <div className="item-image">
                                        <img src={product.image} alt={product.title} />
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                <div className="menu-footer-info">
                    <p>⚠️ Si tienes alguna alergia o intolerancia, por favor indícalo a nuestro equipo.</p>
                </div>
            </div>

            {/* Floating Action Button for Contact/Ordering */}
            <a href="https://wa.me/34640081505" className="floating-cart" title="Hacer pedido">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 11-7.6-10.3c.3 0 .6 0 .9.1" />
                    <path d="M7 11l5 5 10-10" />
                </svg>
            </a>
        </section>
    );
}
