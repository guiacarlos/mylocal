export interface Product {
  name:        string;
  price:       string;
  category:    string;
  allergens:   string[];
  image:       string;
  desc:        string;
  ingredients: string[];
}

export const CATEGORIES = ['Todos', 'Carnes', 'Bowls', 'Pizzas', 'Tacos'] as const;

export const PRODUCTS: Product[] = [
  {
    name: 'Burger Premium', price: '14.50€', category: 'Carnes',
    allergens: ['Gluten', 'Lácteos'],
    image: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&q=80&w=400',
    desc: 'Carne dry-aged 45 días, cheddar fundido, cebolla caramelizada y salsa secreta en pan brioche artesano.',
    ingredients: ['Carne dry-aged', 'Cheddar', 'Cebolla caramelizada', 'Salsa secreta', 'Pan brioche'],
  },
  {
    name: 'Poke Bowl Salmón', price: '12.90€', category: 'Bowls',
    allergens: ['Pescado', 'Sésamo'],
    image: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&q=80&w=400',
    desc: 'Salmón fresco marinado, aguacate, edamame, rábano, mango y base de arroz jazmín con sésamo.',
    ingredients: ['Salmón marinado', 'Aguacate', 'Edamame', 'Rábano', 'Mango', 'Arroz jazmín'],
  },
  {
    name: 'Pizza Trufada', price: '16.00€', category: 'Pizzas',
    allergens: ['Gluten', 'Lácteos'],
    image: 'https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&q=80&w=400',
    desc: 'Mozzarella fior di latte, crema de trufa negra, champiñones portobello y aceite de albahaca fresca.',
    ingredients: ['Mozzarella fior di latte', 'Crema de trufa', 'Portobello', 'Aceite albahaca'],
  },
  {
    name: 'Tacos Al Pastor', price: '9.50€', category: 'Tacos',
    allergens: [],
    image: 'https://images.unsplash.com/photo-1552332386-f8dd00dc2f85?auto=format&fit=crop&q=80&w=400',
    desc: 'Tres tacos de cerdo marinado con piña, cilantro, cebolla morada y salsa verde en tortilla de maíz.',
    ingredients: ['Cerdo marinado', 'Piña', 'Cilantro', 'Cebolla morada', 'Salsa verde', 'Tortilla maíz'],
  },
];
