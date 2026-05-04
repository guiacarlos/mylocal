/**
 * 🌿 OLLAMA ACIDE SOBERANO v2.3
 * 
 * Sin APIs de configuración. Sin dependencias externas. 
 * El Cerebro contiene el Arsenal.
 */

const OllamaChat = {
    baseUrl: 'http://localhost:11434',
    model: 'qwen3-coder:30b',
    conversationHistory: [],
    maxIterations: 10,

    // 🏛️ ARSENAL SOBERANO (Inyectado directamente en el Cerebro)
    tools: [
        { name: 'read_file', description: 'Lee un archivo del búnker.', parameters: { type: 'object', properties: { file_path: { type: 'string' } }, required: ['file_path'] } },
        { name: 'write_file', description: 'Escribe un archivo.', parameters: { type: 'object', properties: { file_path: { type: 'string' }, content: { type: 'string' } }, required: ['file_path', 'content'] } },
        { name: 'patch_file', description: 'Edición quirúrgica. Reemplaza texto específico.', parameters: { type: 'object', properties: { file_path: { type: 'string' }, search: { type: 'string' }, replace: { type: 'string' } }, required: ['file_path', 'search', 'replace'] } },
        { name: 'patch', description: 'Alias de edición quirúrgica.', parameters: { type: 'object', properties: { file_path: { type: 'string' }, search: { type: 'string' }, replace: { type: 'string' } }, required: ['file_path', 'search', 'replace'] } },
        { name: 'list_files', description: 'Lista archivos del búnker.', parameters: { type: 'object', properties: { path: { type: 'string' } } } },
        { name: 'search_code', description: 'Búsqueda global de código.', parameters: { type: 'object', properties: { query: { type: 'string' } }, required: ['query'] } },
        { name: 'search', description: 'Alias de búsqueda global.', parameters: { type: 'object', properties: { query: { type: 'string' } }, required: ['query'] } },
        { name: 'execute_command', description: 'Ejecuta comandos shell.', parameters: { type: 'object', properties: { command: { type: 'string' } }, required: ['command'] } },
        { name: 'git_status', description: 'Estado del repositorio Git.', parameters: { type: 'object', properties: {} } },
        { name: 'git_log', description: 'Log de Git.', parameters: { type: 'object', properties: { limit: { type: 'integer' } } } },
        { name: 'health_check', description: 'Diagnóstico del sistema.', parameters: { type: 'object', properties: {} } },
        { name: 'build_static_site', description: 'Genera el sitio estático.', parameters: { type: 'object', properties: {} } },
        { name: 'create_directory', description: 'Crea directorios.', parameters: { type: 'object', properties: { path: { type: 'string' } }, required: ['path'] } }
    ],

    async send(userMessage) {
        if (this.conversationHistory.length === 0) {
            this.conversationHistory.push({
                role: 'system',
                content: "Eres el Arquitecto de ACIDE. Tienes acceso total al búnker vía herramientas. ÚSALAS sin dudar."
            });
        }

        this.conversationHistory.push({ role: 'user', content: userMessage });

        let iteration = 0;
        let toolLogs = "";

        while (iteration < this.maxIterations) {
            // Formatear herramientas para Ollama nativo
            const formattedTools = this.tools.map(t => ({
                type: 'function',
                function: {
                    name: t.name,
                    description: t.description,
                    parameters: t.parameters
                }
            }));

            const payload = {
                model: this.model,
                messages: this.conversationHistory,
                stream: false,
                tools: formattedTools,
                options: { temperature: 0 }
            };

            const response = await fetch(`${this.baseUrl}/api/chat`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const data = await response.json();
            const message = data.message;

            // Detección de JSON en texto (Resiliencia para modelos locales)
            if (!message.tool_calls || message.tool_calls.length === 0) {
                const pj = this.extractJson(message.content);
                if (pj && pj.name) message.tool_calls = [{ function: { name: pj.name, arguments: pj.arguments || pj.parameters || pj } }];
            }

            this.conversationHistory.push(message);

            if (!message.tool_calls || message.tool_calls.length === 0) {
                return (toolLogs ? toolLogs + "\n" : "") + (message.content || "");
            }

            for (const call of message.tool_calls) {
                const tName = call.function.name;
                const tArgs = call.function.arguments;

                toolLogs += `🛠️ **Acción Real**: [${tName}]\n`;

                try {
                    // LLAMADA DIRECTA AL TÚNEL (api.php restaurado)
                    const result = await window.call(tName, tArgs);
                    this.conversationHistory.push({
                        role: 'tool',
                        content: typeof result === 'string' ? result : JSON.stringify(result),
                        name: tName
                    });
                } catch (e) {
                    this.conversationHistory.push({ role: 'tool', content: `Error: ${e.message}`, name: tName });
                }
            }
            iteration++;
        }
        return toolLogs + "\n⚠️ Límite de recursión.";
    },

    extractJson(s) {
        try {
            if (s.trim().startsWith('{')) return JSON.parse(s.trim());
            const m = s.match(/```json\n([\s\S]*?)\n```/);
            if (m) return JSON.parse(m[1].trim());
        } catch (e) { }
        return null;
    },

    clear() { this.conversationHistory = []; },
    setModel(m) { this.model = m; },
    async listModels() {
        try {
            const r = await fetch(`${this.baseUrl}/api/tags`);
            const d = await r.json();
            return d.models || [];
        } catch (e) { return []; }
    }
};

window.OllamaChat = OllamaChat;
console.log('🏛️ Cerebro de Ollama Sincronizado (Soberanía Total)');
