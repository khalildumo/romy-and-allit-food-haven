import { supabase } from './supabase.js';

export { supabase };

const todoList = document.getElementById('todoList');
const todoStatus = document.getElementById('todoStatus');

if (todoList && todoStatus) {
    async function loadTodos() {
        todoStatus.textContent = 'Loading todos...';

        const { data: todos, error } = await supabase
            .from('todos')
            .select('id, name');

        if (error) {
            console.error('Unable to load todos from Supabase.', error);
            todoStatus.textContent = 'Unable to load todos. Please try again later.';
            return;
        }

        if (todos === null) {
            throw new Error('Supabase returned no todo data.');
        }

        if (todos.length === 0) {
            todoStatus.textContent = 'No todos found.';
            return;
        }

        const items = todos.map((todo) => {
            const item = document.createElement('li');
            item.textContent = String(todo.name ?? '');
            return item;
        });

        todoList.replaceChildren(...items);
        todoStatus.textContent = '';
    }

    loadTodos().catch((error) => {
        console.error('Unable to load todos from Supabase.', error);
        todoStatus.textContent = 'Unable to load todos. Please try again later.';
    });
}
